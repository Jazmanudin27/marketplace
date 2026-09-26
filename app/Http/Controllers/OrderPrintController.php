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

        foreach ($ordersByStore as $storeId => $storeOrders) {
            $store = $storeOrders->first()->store;
            if (!$store || empty($store->access_token)) {
                continue;
            }

            $channelCode = strtolower($store->channel->code ?? '');

            if ($channelCode === 'shopee') {
                try {
                    $orderSns = $storeOrders->pluck('order_marketplace_id')->filter()->values()->toArray();
                    $pdfData = $shopeeService->getOfficialShippingLabelPdf(
                        $store->getValidAccessToken(),
                        (int) $store->marketplace_store_id,
                        $orderSns
                    );
                    if (!empty($pdfData)) {
                        $pdfBuffers[] = $pdfData;
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("[OrderPrintController] Shopee mass print PDF fetch failed for store #{$storeId}: " . $e->getMessage());
                }
            } elseif (in_array($channelCode, ['tiktok', 'tokopedia'])) {
                foreach ($storeOrders as $order) {
                    try {
                        $pdfData = $tiktokService->getOfficialShippingLabelPdf(
                            $store->getValidAccessToken(),
                            $store->shop_cipher ?: $store->marketplace_store_id,
                            $order->order_marketplace_id,
                            $order->package_id
                        );
                        if (!empty($pdfData)) {
                            $pdfBuffers[] = $pdfData;
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning("[OrderPrintController] TikTok print PDF fetch failed for order #{$order->id}: " . $e->getMessage());
                    }
                }
            }
        }

        // Jika kita berhasil menarik PDF resmi dari API, gabungkan menggunakan FPDI dan tampilkan langsung sebagai PDF
        if (!empty($pdfBuffers)) {
            $mergedPdf = $this->mergePdfBuffers($pdfBuffers);
            if (!empty($mergedPdf)) {
                return response($mergedPdf, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="resi_massal_resmi.pdf"',
                ]);
            }
        }

        // Fallback: Generate Pick List data (Summary of all items to pick) jika API PDF tidak tersedia
        $pickList = [];
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $sku = $item->sku ?? 'NO-SKU';
                if (!isset($pickList[$sku])) {
                    $pickList[$sku] = [
                        'name' => $item->product_name,
                        'qty' => 0
                    ];
                }
                $pickList[$sku]['qty'] += $item->quantity;
            }
        }

        return view('orders.mass_print', compact('orders', 'pickList'));
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

        try {
            if ($channelCode === 'shopee' && !empty($store->access_token)) {
                try {
                    $pdfData = $shopeeService->getOfficialShippingLabelPdf(
                        $store->getValidAccessToken(),
                        (int) $store->marketplace_store_id,
                        [$order->order_marketplace_id]
                    );

                    if (!empty($pdfData)) {
                        return response($pdfData, 200, [
                            'Content-Type' => 'application/pdf',
                            'Content-Disposition' => 'inline; filename="resi_shopee_' . $order->order_marketplace_id . '.pdf"',
                        ]);
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("[OrderPrintController] Shopee PDF fetch failed: " . $e->getMessage());
                }
            } elseif (in_array($channelCode, ['tiktok', 'tokopedia']) && !empty($store->access_token)) {
                $pdfData = $tiktokService->getOfficialShippingLabelPdf(
                    $store->getValidAccessToken(),
                    $store->shop_cipher ?: $store->marketplace_store_id,
                    $order->order_marketplace_id,
                    $order->package_id
                );

                if (!empty($pdfData)) {
                    return response($pdfData, 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="resi_tiktok_' . $order->order_marketplace_id . '.pdf"',
                    ]);
                }

                $docData = $tiktokService->getShippingDocument(
                    $store->getValidAccessToken(),
                    $store->shop_cipher ?: $store->marketplace_store_id,
                    $order->order_marketplace_id,
                    $order->package_id
                );

                if (!empty($docData['doc_url'])) {
                    return redirect($docData['doc_url']);
                }

                if (!empty($docData['doc_pdf'])) {
                    return response(base64_decode($docData['doc_pdf']), 200, [
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
     * Penggabung (Merger) PDF resmi marketplace menggunakan FPDI.
     */
    private function mergePdfBuffers(array $pdfBuffers): string
    {
        try {
            $pdf = new \setasign\Fpdi\Fpdi();
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
                    \Illuminate\Support\Facades\Log::warning("[OrderPrintController] mergePdfBuffers inner page exception: " . $e->getMessage());
                }
            }

            if (!$hasPages) {
                return '';
            }

            return $pdf->Output('S');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("[OrderPrintController] mergePdfBuffers error: " . $e->getMessage());
            return '';
        }
    }
}
