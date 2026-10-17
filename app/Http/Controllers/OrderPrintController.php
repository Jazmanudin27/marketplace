<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Services\ShopeeService;
use App\Services\TiktokService;
use Illuminate\Support\Facades\Auth;

class OrderPrintController extends Controller
{
    public function massPrint(
        Request $request,
        ShopeeService $shopeeService,
        TiktokService $tiktokService,
        \App\Services\OrderTrackingService $orderTrackingService
    ) {
        $orderIds = $request->input('order_ids', $request->input('ids', []));
        
        if (empty($orderIds)) {
            return back()->with('error', 'Pilih setidaknya satu pesanan untuk dicetak.');
        }

        $orders = Order::whereIn('id', $orderIds)
            ->where('tenant_id', Auth::user()->tenant_id)
            ->with('items.masterProduct', 'store.channel', 'customer')
            ->get();

        if ($orders->isEmpty()) {
            return back()->with('error', 'Pesanan tidak ditemukan atau Anda tidak memiliki akses.');
        }

        // Otomatis tarik resi dari marketplace API jika belum ada atau kosong
        foreach ($orders as $order) {
            $tracking = trim((string) ($order->tracking_number ?? ''));
            if ($tracking === '' || $tracking === '-') {
                $fetched = $orderTrackingService->fetchTrackingNumber($order);
                if ($fetched) {
                    $order->tracking_number = $fetched;
                    $order->save();
                }
            }
        }

        // Tolak cetak jika setelah dicoba ditarik masih ada pesanan tanpa resi
        $ordersWithoutTracking = $orders->filter(function ($order) {
            $tracking = trim((string) ($order->tracking_number ?? ''));
            return $tracking === '' || $tracking === '-';
        });

        if ($ordersWithoutTracking->isNotEmpty()) {
            // Pastikan pesanan tanpa resi berstatus BELUM DICETAK
            Order::whereIn('id', $ordersWithoutTracking->pluck('id'))->update([
                'is_printed' => false,
                'printed_at' => null,
            ]);

            $missingList = $ordersWithoutTracking->map(function ($o) {
                return $o->invoice_number ?: ($o->order_marketplace_id ?: "#{$o->id}");
            });
            $count = $missingList->count();
            $sample = $missingList->take(5)->implode(', ');
            $extra = $count > 5 ? ' dan ' . ($count - 5) . ' pesanan lainnya' : '';
            $errorMsg = "Cetak resi massal ditolak: Sistem telah mencoba menarik resi otomatis, namun terdapat {$count} pesanan yang nomor resinya belum diterbitkan oleh marketplace/kurir ({$sample}{$extra}). Pastikan pesanan sudah diproses di Seller Center sebelum mencetak.";

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $errorMsg], 422);
            }

            return response()->view('orders.print_error', compact('ordersWithoutTracking'), 422);
        }

        // Tandai pesanan sebagai SUDAH DIPRINT
        Order::whereIn('id', $orders->pluck('id'))->update([
            'is_printed' => true,
            'printed_at' => now(),
        ]);

        // Coba tarik PDF resmi marketplace (Shopee / TikTok) untuk seluruh pesanan dan gabungkan dengan FPDI jika terhubung API
        $pdfBuffers = [];
        $ordersByStore = $orders->groupBy('store_id');

        $fetchErrors = [];

        foreach ($ordersByStore as $storeId => $storeOrders) {
            $store = $storeOrders->first()->store;
            if (!$store || empty($store->access_token)) {
                continue;
            }

            $channelCode = strtolower($store->channel->code ?? '');
            $cacheDir = storage_path('app/shipping_labels');
            if (!file_exists($cacheDir)) {
                @mkdir($cacheDir, 0777, true);
            }

            if ($channelCode === 'shopee') {
                // Shopee mewajibkan order dalam 1 batch create_shipping_document memiliki kurir yang sama
                $ordersByCourier = $storeOrders->groupBy(function ($ord) {
                    return strtoupper(trim($ord->courier ?: 'SPX'));
                });

                foreach ($ordersByCourier as $courierKey => $courierOrders) {
                    $orderSns = $courierOrders->pluck('order_marketplace_id')->filter()->values()->toArray();
                    
                    // Coba batch per kurir terlebih dahulu
                    $batchSuccess = false;
                    try {
                        $pdfData = $shopeeService->getOfficialShippingLabelPdf(
                            $store->getValidAccessToken(),
                            (int) $store->marketplace_store_id,
                            $orderSns
                        );
                        if (!empty($pdfData)) {
                            $this->addPdfBuffer($pdfBuffers, $pdfData);
                            $batchSuccess = true;
                        }
                    } catch (\Throwable $e) {
                        $fetchErrors[] = "[Shopee Batch] " . $e->getMessage();
                        \Illuminate\Support\Facades\Log::warning("[OrderPrintController] Shopee batch print for courier {$courierKey} failed: " . $e->getMessage() . ". Retrying per order...");
                    }

                    // Jika batch gagal, tarik per pesanan agar pesanan yang valid tetap berhasil mendapatkan PDF
                    if (!$batchSuccess) {
                        foreach ($courierOrders as $singleOrd) {
                            $cacheFile = $cacheDir . '/shopee_' . $singleOrd->order_marketplace_id . '.pdf';
                            if (file_exists($cacheFile) && filesize($cacheFile) > 500) {
                                $this->addPdfBuffer($pdfBuffers, file_get_contents($cacheFile));
                                continue;
                            }

                            try {
                                $singlePdf = $shopeeService->getOfficialShippingLabelPdf(
                                    $store->getValidAccessToken(),
                                    (int) $store->marketplace_store_id,
                                    [$singleOrd->order_marketplace_id]
                                );
                                if (!empty($singlePdf)) {
                                    @file_put_contents($cacheFile, $singlePdf);
                                    $this->addPdfBuffer($pdfBuffers, $singlePdf);
                                }
                            } catch (\Throwable $singleEx) {
                                $fetchErrors[] = "[Shopee {$singleOrd->order_marketplace_id}] " . $singleEx->getMessage();
                                \Illuminate\Support\Facades\Log::warning("[OrderPrintController] Shopee single print PDF failed for order #{$singleOrd->id} ({$singleOrd->order_marketplace_id}): " . $singleEx->getMessage());
                            }
                        }
                    }
                }
            } elseif (in_array($channelCode, ['tiktok', 'tokopedia'])) {
                $accessToken = $store->getValidAccessToken();
                $shopCipher = $store->getValidShopCipher();

                // 1. Resolve package_id untuk order TikTok yang belum tersimpan package_id-nya
                $ordersNeedingPkg = $storeOrders->filter(fn($o) => empty($o->package_id));
                if ($ordersNeedingPkg->isNotEmpty()) {
                    try {
                        $mpIds = $ordersNeedingPkg->pluck('order_marketplace_id')->filter()->values()->toArray();
                        if (!empty($mpIds)) {
                            $orderData = $tiktokService->getOrderDetail($accessToken, $shopCipher, $mpIds);
                            $tOrders = $orderData['orders'] ?? $orderData['order_list'] ?? [];
                            foreach ($tOrders as $tOrd) {
                                $tOrdId = (string) ($tOrd['id'] ?? $tOrd['order_id'] ?? '');
                                $pkgs = array_merge($tOrd['package_list'] ?? [], $tOrd['packages'] ?? []);
                                $foundPkgId = null;
                                foreach ($pkgs as $p) {
                                    $pId = $p['package_id'] ?? $p['id'] ?? null;
                                    if (!empty($pId)) {
                                        $foundPkgId = (string) $pId;
                                        break;
                                    }
                                }
                                if (empty($foundPkgId) && !empty($tOrd['package_id'])) {
                                    $foundPkgId = (string) $tOrd['package_id'];
                                }

                                if (!empty($foundPkgId)) {
                                    $matchedOrders = $storeOrders->where('order_marketplace_id', $tOrdId);
                                    foreach ($matchedOrders as $mOrd) {
                                        $mOrd->package_id = $foundPkgId;
                                        $mOrd->save();
                                    }
                                }
                            }
                        }
                    } catch (\Throwable $batchEx) {
                        \Illuminate\Support\Facades\Log::warning("[OrderPrintController] TikTok batch getOrderDetail package_id failed: " . $batchEx->getMessage());
                    }
                }

                // 2. Fetch PDF resmi per pesanan
                foreach ($storeOrders as $order) {
                    $cacheFile = $cacheDir . '/tiktok_' . $order->order_marketplace_id . '.pdf';

                    // Gunakan PDF resmi tersimpan jika sebelumnya sudah pernah ditarik (agar tetap bisa dicetak pasca pickup)
                    if (file_exists($cacheFile) && filesize($cacheFile) > 500) {
                        $this->addPdfBuffer($pdfBuffers, file_get_contents($cacheFile));
                        continue;
                    }

                    // Jika masih belum ada package_id, coba arrange shipping lewat OrderTrackingService
                    if (empty($order->package_id)) {
                        try {
                            $orderTrackingService->fetchTrackingNumber($order);
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::warning("[OrderPrintController] TikTok auto-ship attempt failed for order #{$order->id}: " . $e->getMessage());
                        }
                    }

                    $fetchedPdf = null;
                    try {
                        $fetchedPdf = $tiktokService->getOfficialShippingLabelPdf(
                            $accessToken,
                            $shopCipher,
                            $order->order_marketplace_id,
                            $order->package_id
                        );
                    } catch (\Throwable $e) {
                        $fetchErrors[] = "[TikTok {$order->order_marketplace_id}] " . $e->getMessage();
                        \Illuminate\Support\Facades\Log::warning("[OrderPrintController] TikTok getOfficialShippingLabelPdf failed for order #{$order->id}: " . $e->getMessage());
                    }

                    // Fallback jika URL/PDF base64 dari getShippingDocument
                    if (empty($fetchedPdf)) {
                        try {
                            $docData = $tiktokService->getShippingDocument(
                                $accessToken,
                                $shopCipher,
                                $order->order_marketplace_id,
                                $order->package_id
                            );
                            if (!empty($docData['doc_pdf'])) {
                                $fetchedPdf = base64_decode($docData['doc_pdf']);
                            } elseif (!empty($docData['doc_url'])) {
                                $docUrl = html_entity_decode($docData['doc_url']);
                                $dlRes = \Illuminate\Support\Facades\Http::withoutVerifying()
                                    ->withOptions(['allow_redirects' => true])
                                    ->timeout(30)
                                    ->withHeaders([
                                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                                        'Accept'     => '*/*',
                                    ])->get($docUrl);
                                if ($dlRes->successful() && !empty($dlRes->body())) {
                                    $fetchedPdf = $dlRes->body();
                                }
                            }
                        } catch (\Throwable $e) {
                            $fetchErrors[] = "[TikTok {$order->order_marketplace_id} doc] " . $e->getMessage();
                            \Illuminate\Support\Facades\Log::warning("[OrderPrintController] TikTok getShippingDocument fallback failed for order #{$order->id}: " . $e->getMessage());
                        }
                    }

                    if (!empty($fetchedPdf)) {
                        @file_put_contents($cacheFile, $fetchedPdf);
                        $this->addPdfBuffer($pdfBuffers, $fetchedPdf);
                    }
                }
            }
        }

        // Jika kita berhasil menarik PDF resmi dari API, tampilkan langsung atau gabungkan
        if (!empty($pdfBuffers)) {
            // Jika hanya 1 buffer (atau 1 pesanan)
            if (count($pdfBuffers) === 1) {
                $firstOrder = $orders->first();
                $channelName = strtolower($firstOrder->store->channel->code ?? 'marketplace');
                $orderCode = $firstOrder->order_marketplace_id ?: $firstOrder->id;
                $filename = "resi_{$channelName}_{$orderCode}.pdf";

                return response($pdfBuffers[0], 200, [
                    'Content-Type'        => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $filename . '"',
                ]);
            }

            // Jika lebih dari 1 buffer, coba gabungkan di server menggunakan Ghostscript / pdfunite / qpdf / FPDI
            $mergedPdf = $this->mergePdfBuffers($pdfBuffers);
            if (!empty($mergedPdf)) {
                return response($mergedPdf, 200, [
                    'Content-Type'        => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="resi_massal_resmi_' . date('Ymd_His') . '.pdf"',
                ]);
            }

            // Jika merger server tidak tersedia (CLI/FPDI limitasi PDF 1.7), gabungkan secara instan di browser dengan pdf-lib
            $pdfBase64List = array_map(function ($buf) {
                return base64_encode($buf);
            }, $pdfBuffers);

            return response()->view('orders.merge_print', compact('pdfBase64List'));
        }

        // Jika toko terhubung API marketplace tapi PDF resmi gagal diunduh
        $hasApiStore = $orders->contains(function ($ord) {
            return !empty($ord->store?->access_token);
        });

        if ($hasApiStore) {
            $isPickupError = collect($fetchErrors)->contains(fn($err) => str_contains(strtolower($err), 'pickup') || str_contains(strtolower($err), 'shipped'));
            
            $explanation = $isPickupError
                ? "Pesanan yang dipilih <strong>sudah di-pickup / diserahkan ke kurir</strong>.<br><br>Kebijakan resmi <strong>TikTok Shop Open API</strong> secara permanen mengunci pencetakan dokumen resi setelah kurir melakukan scan pickup (Error: <em>Documents couldn't be printed after the package has been pickup</em>).<br><br><strong>Solusi:</strong> Silakan pilih dan centang pesanan TikTok yang masih berstatus <strong>'Perlu Dikirim' / 'Siap Kirim' (belum di-pickup kurir)</strong> untuk mencetak dokumen resi PDF asli dari marketplace."
                : "Dokumen PDF resi resmi belum dapat diunduh dari API marketplace. Pastikan pesanan sudah diproses dan resi sudah terbit di Seller Center.";

            $errorDetails = !empty($fetchErrors) ? '<div style="margin-top:15px;padding:12px;background:#fef2f2;border-radius:6px;font-family:monospace;font-size:12px;color:#991b1b;word-break:break-all;border:1px solid #fecaca;">' . implode('<br>', array_map('htmlspecialchars', array_unique($fetchErrors))) . '</div>' : '';

            return response("<!DOCTYPE html><html><head><title>Gagal Unduh Resi Resmi Marketplace</title><meta name='viewport' content='width=device-width, initial-scale=1.0'></head><body style='margin:0;padding:20px;background:#f8fafc;font-family:system-ui,-apple-system,sans-serif;'><div style='max-width:640px;margin:50px auto;background:#fff;border-radius:12px;padding:32px;box-shadow:0 10px 25px -5px rgba(0,0,0,0.08);border:1px solid #fee2e2;'><div style='width:52px;height:52px;border-radius:50%;background:#fee2e2;display:flex;align-items:center;justify-content:center;margin-bottom:20px;'><svg width='28' height='28' viewBox='0 0 24 24' fill='none' stroke='#dc2626' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='12' r='10'></circle><line x1='12' y1='8' x2='12' y2='12'></line><line x1='12' y1='16' x2='12.01' y2='16'></line></svg></div><h2 style='margin:0 0 12px;color:#991b1b;font-size:20px;'>Gagal Mengunduh Resi Resmi Marketplace</h2><p style='color:#334155;line-height:1.6;font-size:14px;margin:0 0 16px;'>{$explanation}</p>{$errorDetails}<div style='margin-top:24px;display:flex;gap:12px;'><button onclick='window.close()' style='padding:10px 20px;background:#dc2626;color:#fff;border:none;border-radius:8px;font-weight:600;font-size:14px;cursor:pointer;'>Tutup Halaman</button><button onclick='history.back()' style='padding:10px 20px;background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;border-radius:8px;font-weight:600;font-size:14px;cursor:pointer;'>Kembali ke Daftar Pesanan</button></div></div></body></html>", 400)->header('Content-Type', 'text/html');
        }

        // Jika toko manual/offline tanpa API marketplace sama sekali, gunakan format thermal lokal ERP
        return view('orders.mass_print', compact('orders'));
    }

    /**
     * Stream file PDF resi resmi marketplace dari API untuk 1 pesanan (digunakan di iframe bulk print).
     */
    public function streamOfficialPdf(
        Order $order,
        ShopeeService $shopeeService,
        TiktokService $tiktokService
    ) {
        abort_unless($order->tenant_id === Auth::user()->tenant_id, 403);

        $store = $order->store;
        if (!$store) {
            abort(404, 'Store tidak ditemukan.');
        }

        $channelCode = strtolower($store->channel->code ?? '');

        $cacheDir = storage_path('app/shipping_labels');
        if (!file_exists($cacheDir)) {
            @mkdir($cacheDir, 0777, true);
        }
        $cacheFile = $cacheDir . '/' . $channelCode . '_' . $order->order_marketplace_id . '.pdf';

        if (file_exists($cacheFile) && filesize($cacheFile) > 500) {
            return response(file_get_contents($cacheFile), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="resi_' . $channelCode . '_' . $order->order_marketplace_id . '.pdf"',
            ]);
        }

        try {
            if ($channelCode === 'shopee' && !empty($store->access_token)) {
                try {
                    $pdfData = $shopeeService->getOfficialShippingLabelPdf(
                        $store->getValidAccessToken(),
                        (int) $store->marketplace_store_id,
                        [$order->order_marketplace_id]
                    );

                    if (!empty($pdfData)) {
                        @file_put_contents($cacheFile, $pdfData);
                        return response($pdfData, 200, [
                            'Content-Type' => 'application/pdf',
                            'Content-Disposition' => 'inline; filename="resi_shopee_' . $order->order_marketplace_id . '.pdf"',
                        ]);
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("[OrderPrintController] Shopee PDF fetch failed: " . $e->getMessage());
                }
            } elseif (in_array($channelCode, ['tiktok', 'tokopedia']) && !empty($store->access_token)) {
                $shopCipher = $store->getValidShopCipher();
                $packageId = $order->package_id;

                if (empty($packageId)) {
                    try {
                        $orderData = $tiktokService->getOrderDetail($store->getValidAccessToken(), $shopCipher, [$order->order_marketplace_id]);
                        $tOrders = $orderData['orders'] ?? $orderData['order_list'] ?? [];
                        if (!empty($tOrders[0])) {
                            $pkgs = array_merge($tOrders[0]['packages'] ?? [], $tOrders[0]['package_list'] ?? []);
                            foreach ($pkgs as $p) {
                                $pId = $p['package_id'] ?? $p['id'] ?? null;
                                if (!empty($pId)) {
                                    $packageId = (string) $pId;
                                    break;
                                }
                            }
                            if (empty($packageId) && !empty($tOrders[0]['package_id'])) {
                                $packageId = (string) $tOrders[0]['package_id'];
                            }
                            if (!empty($packageId)) {
                                $order->package_id = $packageId;
                                $order->save();
                            }
                        }
                    } catch (\Throwable $ex) {
                        \Illuminate\Support\Facades\Log::warning("[OrderPrintController] streamOfficialPdf auto-fetch package_id failed: " . $ex->getMessage());
                    }
                }

                $pdfData = $tiktokService->getOfficialShippingLabelPdf(
                    $store->getValidAccessToken(),
                    $shopCipher,
                    $order->order_marketplace_id,
                    $packageId
                );

                if (!empty($pdfData)) {
                    @file_put_contents($cacheFile, $pdfData);
                    return response($pdfData, 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="resi_tiktok_' . $order->order_marketplace_id . '.pdf"',
                    ]);
                }

                $docData = $tiktokService->getShippingDocument(
                    $store->getValidAccessToken(),
                    $shopCipher,
                    $order->order_marketplace_id,
                    $packageId
                );

                if (!empty($docData['doc_url'])) {
                    return redirect($docData['doc_url']);
                }

                if (!empty($docData['doc_pdf'])) {
                    $decoded = base64_decode($docData['doc_pdf']);
                    @file_put_contents($cacheFile, $decoded);
                    return response($decoded, 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="resi_tiktok_' . $order->order_marketplace_id . '.pdf"',
                    ]);
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("[OrderPrintController] streamOfficialPdf failed for order #{$order->id}: " . $e->getMessage());
        }

        // Fallback to HTML label view with stored official API routing_code
        return view('orders.print', compact('order'));
    }

    /**
     * Penggabung (Merger) PDF resmi marketplace menggunakan Ghostscript / pdfunite / qpdf / FPDI.
     */
    private function mergePdfBuffers(array $pdfBuffers): string
    {
        if (empty($pdfBuffers)) {
            return '';
        }

        if (count($pdfBuffers) === 1) {
            return reset($pdfBuffers);
        }

        // 1. Coba CLI tools terlebih dahulu (mendukung PDF 1.5 - 2.0 tanpa batasan parser)
        $cliMerged = $this->mergePdfWithCli($pdfBuffers);
        if (!empty($cliMerged)) {
            return $cliMerged;
        }

        // 2. Fallback ke FPDI jika PDF mendukung versi standar
        return $this->mergePdfWithFpdi($pdfBuffers);
    }

    /**
     * Merge multiple PDF buffers using available Linux CLI tools (gs, pdfunite, qpdf).
     */
    private function mergePdfWithCli(array $pdfBuffers): string
    {
        $tempDir = storage_path('app/temp_pdf');
        if (!file_exists($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }

        $tempFiles = [];
        foreach ($pdfBuffers as $i => $buffer) {
            if (!is_string($buffer) || strlen($buffer) < 10) {
                continue;
            }
            $file = $tempDir . '/tmp_in_' . uniqid() . '_' . $i . '.pdf';
            file_put_contents($file, $buffer);
            $tempFiles[] = $file;
        }

        if (count($tempFiles) === 0) {
            return '';
        }

        if (count($tempFiles) === 1) {
            $content = file_get_contents($tempFiles[0]);
            $this->cleanupFiles($tempFiles);
            return $content;
        }

        $outputFile = $tempDir . '/tmp_out_' . uniqid() . '.pdf';
        $escapedInputs = implode(' ', array_map('escapeshellarg', $tempFiles));
        $escapedOutput = escapeshellarg($outputFile);

        // CLI commands ordered by prevalence and robustness
        $commands = [
            // Ghostscript
            "gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile={$escapedOutput} {$escapedInputs} 2>&1",
            "/usr/bin/gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile={$escapedOutput} {$escapedInputs} 2>&1",
            // pdfunite (poppler-utils)
            "pdfunite {$escapedInputs} {$escapedOutput} 2>&1",
            "/usr/bin/pdfunite {$escapedInputs} {$escapedOutput} 2>&1",
            // qpdf
            "qpdf --empty --pages {$escapedInputs} -- {$escapedOutput} 2>&1",
            "/usr/bin/qpdf --empty --pages {$escapedInputs} -- {$escapedOutput} 2>&1",
        ];

        foreach ($commands as $cmd) {
            $output = [];
            $returnVar = 1;
            @exec($cmd, $output, $returnVar);

            if ($returnVar === 0 && file_exists($outputFile) && filesize($outputFile) > 100) {
                $mergedContent = file_get_contents($outputFile);
                $this->cleanupFiles(array_merge($tempFiles, [$outputFile]));
                return $mergedContent;
            }
        }

        $this->cleanupFiles(array_merge($tempFiles, [$outputFile]));
        return '';
    }

    /**
     * Merge multiple PDF buffers using FPDI PHP library.
     */
    private function mergePdfWithFpdi(array $pdfBuffers): string
    {
        try {
            if (!class_exists('\setasign\Fpdi\Fpdi') && !class_exists('\Setasign\Fpdi\Fpdi')) {
                return '';
            }
            $className = class_exists('\setasign\Fpdi\Fpdi') ? '\setasign\Fpdi\Fpdi' : '\Setasign\Fpdi\Fpdi';
            $pdf = new $className();
            $hasPages = false;

            foreach ($pdfBuffers as $buffer) {
                if (empty($buffer) || !is_string($buffer)) {
                    continue;
                }

                try {
                    $stream = \setasign\Fpdi\PdfParser\StreamReader::createByString($buffer);
                    $pageCount = $pdf->setSourceFile($stream);

                    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                        $templateId = $pdf->importPage($pageNo);
                        $size = $pdf->getTemplateSize($templateId);

                        $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                        $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                        $pdf->useTemplate($templateId);
                        $hasPages = true;
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("[OrderPrintController] mergePdfWithFpdi page error: " . $e->getMessage());
                }
            }

            if (!$hasPages) {
                return '';
            }

            return $pdf->Output('S');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("[OrderPrintController] mergePdfWithFpdi error: " . $e->getMessage());
            return '';
        }
    }

    private function cleanupFiles(array $files): void
    {
        foreach ($files as $f) {
            if (file_exists($f)) {
                @unlink($f);
            }
        }
    }

    /**
     * Menambahkan buffer PDF dan otomatis mengekstrak file ZIP jika Shopee mengembalikan arsip ZIP.
     */
    private function addPdfBuffer(array &$pdfBuffers, ?string $data): void
    {
        if (!is_string($data) || strlen($data) < 10) {
            return;
        }

        // Cek jika data adalah ZIP archive (Shopee sering mengemas beberapa PDF ke ZIP)
        if (str_starts_with($data, "\x50\x4b")) {
            try {
                $tempZip = tempnam(sys_get_temp_dir(), 'shp_zip_');
                file_put_contents($tempZip, $data);
                $zip = new \ZipArchive();
                if ($zip->open($tempZip) === true) {
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $stat = $zip->statIndex($i);
                        if (str_ends_with(strtolower($stat['name'] ?? ''), '.pdf')) {
                            $extracted = $zip->getFromIndex($i);
                            if (!empty($extracted)) {
                                if (str_contains(substr($extracted, 0, 2048), '%PDF')) {
                                    $pos = strpos($extracted, '%PDF');
                                    $pdfBuffers[] = $pos > 0 ? substr($extracted, $pos) : $extracted;
                                }
                            }
                        }
                    }
                    $zip->close();
                }
                @unlink($tempZip);
                return;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("[OrderPrintController] Unzip buffer failed: " . $e->getMessage());
            }
        }

        // Normalisasi data jika berformat base64
        if (!str_contains(substr($data, 0, 64), '%PDF')) {
            $decoded = base64_decode($data, true);
            if ($decoded && str_contains(substr($decoded, 0, 64), '%PDF')) {
                $data = $decoded;
            }
        }

        if (str_contains(substr($data, 0, 2048), '%PDF')) {
            $pos = strpos($data, '%PDF');
            $pdfBuffers[] = $pos > 0 ? substr($data, $pos) : $data;
        }
    }
}
