<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Store;
use App\Models\Customer;
use App\Traits\SalesReportCalculationTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LaporanPenjualanController extends Controller
{
    use SalesReportCalculationTrait;

    /**
     * Display V2 Laporan Penjualan with Tabs (Penjualan Dilepas & Rekap Penjualan).
     */
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $categories = Category::where('tenant_id', $tenantId)->orderBy('name')->get();
        $brands     = Brand::where('tenant_id', $tenantId)->orderBy('name')->get();
        $stores     = Store::where('tenant_id', $tenantId)->with('channel')->orderBy('store_name')->get();

        // Customer Categories for sales report tab
        $masterCustCats = Customer::where('tenant_id', $tenantId)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->pluck('category')
            ->unique()
            ->toArray();
        $customerCategories = array_values(array_unique(array_merge(array_keys(Customer::CATEGORIES), $masterCustCats)));
        $customerCategoryLabels = Customer::CATEGORIES;

        // Current active tab: 'dilepas' or 'semua'
        $activeTab = $request->get('tab', 'dilepas');

        // Filter Parameters
        $dateFrom       = $request->get('date_from', Carbon::now()->startOfMonth()->toDateString());
        $dateTo         = $request->get('date_to', Carbon::now()->toDateString());
        $categoryId     = $request->get('category_id');
        $brandId        = $request->get('brand_id');
        $storeId        = $request->get('store_id');
        $channelCode    = $request->get('channel_code', 'online');
        $reportFormat   = $request->get('report_format', ($activeTab === 'dilepas' ? 'per_produk' : 'per_produk'));
        $poStatus       = $request->get('po_status');
        $customerCat    = $request->get('customer_category', 'all');
        $dropshipFilter = $request->get('is_dropship', 'all');
        $statusFilter   = $request->get('status', 'all');

        // Summary calculated self-contained via SalesReportCalculationTrait
        $summary = $this->getReleasedSalesSummary(
            $tenantId,
            $dateFrom,
            $dateTo,
            $channelCode === 'all' ? 'online' : $channelCode,
            'all',
            $storeId
        );

        return view('v2.laporan.index', compact(
            'activeTab',
            'categories',
            'brands',
            'stores',
            'customerCategories',
            'customerCategoryLabels',
            'dateFrom',
            'dateTo',
            'categoryId',
            'brandId',
            'storeId',
            'channelCode',
            'reportFormat',
            'poStatus',
            'customerCat',
            'dropshipFilter',
            'statusFilter',
            'summary'
        ));
    }

    /**
     * Cetak Laporan Penjualan Dilepas (Dana Cair) - V2
     */
    public function printReleasedSalesReport(Request $request)
    {
        $tenantId       = Auth::user()->tenant_id;
        $dateFrom       = $request->input('date_from', date('Y-m-01'));
        $dateTo         = $request->input('date_to', date('Y-m-d'));
        $categoryId     = $request->input('category_id');
        $brandId        = $request->input('brand_id');
        $isBundle       = $request->input('is_bundle');
        $storeId        = $request->input('store_id');
        $channelCode    = $request->input('channel_code', 'online');
        if ($channelCode === 'all') $channelCode = 'online';

        $reportFormat   = $request->input('report_format', 'per_produk');
        $search         = $request->input('search');
        $hideZeroSales  = $request->boolean('hide_zero_sales');

        $isPo = null;
        $customerCat = 'all';
        $statusFilter = 'completed'; // Strictly completed / released sales

        if ($reportFormat === 'ringkasan_penghasilan') {
            $data = $this->getIncomeStatementData($tenantId, $dateFrom, $dateTo, $channelCode, $customerCat, $statusFilter, $storeId, 'completed_at');
            $storeObj = $storeId ? Store::with('channel')->find($storeId) : null;
            return view('v2.laporan.print_income_statement', array_merge($data, [
                'dateFrom' => $dateFrom, 
                'dateTo' => $dateTo,
                'store' => $storeObj,
                'title' => 'Laporan Ringkasan Penghasilan & Biaya Escrow Marketplace'
            ]));
        } elseif ($reportFormat === 'per_channel') {
            $data = $this->getSalesReportPerChannelData($tenantId, $dateFrom, $dateTo, $channelCode, $customerCat, $statusFilter, $storeId, 'completed_at');
            return view('v2.laporan.print_sales_report_channel', array_merge($data, [
                'dateFrom' => $dateFrom, 
                'dateTo' => $dateTo, 
                'customerCat' => $customerCat,
                'title' => 'Laporan Penjualan Dilepas Per Toko Marketplace'
            ]));
        } elseif ($reportFormat === 'detail') {
            $data = $this->getSalesReportDetailData($tenantId, $dateFrom, $dateTo, $categoryId, $brandId, $channelCode, $customerCat, $statusFilter, $search, $isBundle, $isPo, $storeId, 'completed_at');
            return view('v2.laporan.print_sales_report_detail', array_merge($data, [
                'dateFrom' => $dateFrom, 
                'dateTo' => $dateTo,
                'title' => 'Laporan Detail Transaksi Penjualan Dilepas'
            ]));
        } elseif ($reportFormat === 'per_tanggal') {
            $data = $this->getSalesReportPerDateData($tenantId, $dateFrom, $dateTo, $channelCode, $customerCat, $statusFilter, $storeId, 'completed_at');
            return view('v2.laporan.print_sales_report_date', array_merge($data, [
                'dateFrom' => $dateFrom, 
                'dateTo' => $dateTo,
                'title' => 'Laporan Penjualan Dilepas Per Tanggal'
            ]));
        } else {
            // Default: per_produk
            $data = $this->getSalesReportData($tenantId, $dateFrom, $dateTo, $categoryId, $brandId, $channelCode, $customerCat, $statusFilter, $search, $hideZeroSales, $isBundle, $isPo, $storeId, 'completed_at');
            return view('v2.laporan.print_sales_report', array_merge($data, [
                'dateFrom' => $dateFrom, 
                'dateTo' => $dateTo,
                'title' => 'Laporan Rekap Penjualan Produk Dilepas (Dana Cair)'
            ]));
        }
    }

    /**
     * Export CSV Laporan Penjualan Dilepas (Dana Cair) - V2
     */
    public function exportReleasedSalesReport(Request $request)
    {
        $tenantId       = Auth::user()->tenant_id;
        $dateFrom       = $request->input('date_from', date('Y-m-01'));
        $dateTo         = $request->input('date_to', date('Y-m-d'));
        $categoryId     = $request->input('category_id');
        $brandId        = $request->input('brand_id');
        $isBundle       = $request->input('is_bundle');
        $storeId        = $request->input('store_id');
        $channelCode    = $request->input('channel_code', 'online');
        if ($channelCode === 'all') $channelCode = 'online';

        $reportFormat   = $request->input('report_format', 'ringkasan_penghasilan');
        $search         = $request->input('search');
        $hideZeroSales  = $request->boolean('hide_zero_sales');
        $isPo           = null;
        $customerCat    = 'all';
        $statusFilter   = 'completed';

        $filename = "Laporan_Penjualan_Dilepas_" . date('Ymd_His') . ".csv";
        $headers = [
            "Content-Type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        if ($reportFormat === 'per_channel') {
            $channelData = $this->getSalesReportPerChannelData($tenantId, $dateFrom, $dateTo, $channelCode, $customerCat, $statusFilter, $storeId, 'completed_at');
            $callback = function () use ($channelData) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

                fputcsv($file, [
                    'No.',
                    'Toko Marketplace / Saluran',
                    'Tipe Saluran',
                    'Jumlah Order',
                    'Total Item Terjual',
                    'Omset Kotor (Rp)',
                    'Refund (Rp)',
                    'Total Potongan Marketplace (Rp)',
                    'Dana Dilepas Net (Rp)',
                    'HPP (Rp)',
                    'Margin (Rp)',
                    'Margin %'
                ]);

                foreach ($channelData['channels'] as $idx => $row) {
                    fputcsv($file, [
                        $idx + 1,
                        $row['name'],
                        $row['type'],
                        $row['orders'],
                        $row['qty'],
                        (int)$row['omset'],
                        (int)($row['refund'] ?? 0),
                        (int)($row['total_fee'] ?? 0),
                        (int)($row['net_released'] ?? 0),
                        (int)($row['hpp'] ?? 0),
                        (int)($row['margin_rp'] ?? 0),
                        ($row['margin_pct'] ?? 0) . '%'
                    ]);
                }

                fputcsv($file, [
                    '',
                    'TOTAL REKAPITULASI',
                    '',
                    $channelData['grandTotalOrders'],
                    $channelData['grandTotalQty'],
                    (int)$channelData['grandTotalOmset'],
                    (int)($channelData['grandTotalRefund'] ?? 0),
                    (int)($channelData['grandMarketplaceFee'] ?? 0),
                    (int)($channelData['grandNetReleased'] ?? 0),
                    (int)($channelData['grandTotalHpp'] ?? 0),
                    (int)($channelData['grandTotalMarginRp'] ?? 0),
                    ($channelData['grandOverallMarginPct'] ?? 0) . '%'
                ]);

                fclose($file);
            };
        } elseif ($reportFormat === 'per_tanggal') {
            $dateData = $this->getSalesReportPerDateData($tenantId, $dateFrom, $dateTo, $channelCode, $customerCat, $statusFilter, $storeId, 'completed_at');
            $callback = function () use ($dateData) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

                fputcsv($file, [
                    'No.',
                    'Tanggal',
                    'Total Qty Terjual',
                    'Omset Kotor (Rp)',
                    'Refund (Rp)',
                    'Total Potongan Marketplace (Rp)',
                    'Dana Dilepas Net (Rp)',
                    'HPP (Rp)',
                    'Margin (Rp)',
                    'Margin %'
                ]);

                foreach ($dateData['dates'] as $idx => $row) {
                    fputcsv($file, [
                        $idx + 1,
                        $row['date'],
                        $row['total_qty'],
                        (int)$row['total_omset'],
                        (int)($row['refund'] ?? 0),
                        (int)($row['total_fee'] ?? 0),
                        (int)($row['net_released'] ?? 0),
                        (int)($row['hpp'] ?? 0),
                        (int)($row['margin_rp'] ?? 0),
                        ($row['margin_pct'] ?? 0) . '%'
                    ]);
                }

                fputcsv($file, [
                    '',
                    'TOTAL REKAPITULASI',
                    $dateData['grandTotalQty'],
                    (int)$dateData['grandTotalOmset'],
                    (int)($dateData['grandTotalRefund'] ?? 0),
                    (int)($dateData['grandMarketplaceFee'] ?? 0),
                    (int)($dateData['grandNetReleased'] ?? 0),
                    (int)($dateData['grandTotalHpp'] ?? 0),
                    (int)($dateData['grandTotalMarginRp'] ?? 0),
                    ($dateData['grandOverallMarginPct'] ?? 0) . '%'
                ]);

                fclose($file);
            };
        } elseif ($reportFormat === 'per_produk') {
            $productData = $this->getSalesReportData($tenantId, $dateFrom, $dateTo, $categoryId, $brandId, $channelCode, $customerCat, $statusFilter, $search, $hideZeroSales, $isBundle, $isPo, $storeId, 'completed_at');
            $callback = function () use ($productData) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

                fputcsv($file, [
                    'No.',
                    'SKU',
                    'Nama Produk',
                    'Kategori',
                    'Brand',
                    'Qty Terjual',
                    'Harga Satuan (Rp)',
                    'Omset Kotor (Rp)',
                    'Total Potongan Marketplace (Rp)',
                    'Dana Dilepas Net (Rp)',
                    'HPP Modal (Rp)',
                    'Margin (Rp)',
                    'Margin %'
                ]);

                foreach ($productData['items'] as $idx => $item) {
                    $unitPrice = $item['qty_total'] > 0 ? (int)($item['total_omset'] / $item['qty_total']) : 0;
                    fputcsv($file, [
                        $idx + 1,
                        $item['sku'],
                        $item['name'],
                        $item['category_name'],
                        $item['brand_name'],
                        $item['qty_total'],
                        $unitPrice,
                        (int)$item['total_omset'],
                        (int)($item['total_fee'] ?? 0),
                        (int)($item['net_released'] ?? 0),
                        (int)($item['total_hpp'] ?? 0),
                        (int)($item['margin_rp'] ?? 0),
                        ($item['margin_pct'] ?? 0) . '%'
                    ]);
                }

                fputcsv($file, [
                    '',
                    'TOTAL REKAPITULASI',
                    '',
                    '',
                    '',
                    $productData['grandTotalQty'],
                    '',
                    (int)$productData['grandTotalOmset'],
                    (int)($productData['grandMarketplaceFee'] ?? 0),
                    (int)($productData['grandNetReleased'] ?? 0),
                    (int)($productData['grandTotalHpp'] ?? 0),
                    (int)($productData['grandTotalMarginRp'] ?? 0),
                    ($productData['grandOverallMarginPct'] ?? 0) . '%'
                ]);

                fclose($file);
            };
        } else {
            $detailData = $this->getSalesReportDetailData($tenantId, $dateFrom, $dateTo, $categoryId, $brandId, $channelCode, $customerCat, $statusFilter, $search, $isBundle, $isPo, $storeId, 'completed_at');
            $callback = function () use ($detailData) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

                fputcsv($file, [
                    'No.',
                    'Tanggal Order',
                    'Tanggal Dilepas',
                    'No. Pesanan',
                    'Toko / Channel',
                    'Pelanggan',
                    'Ringkasan Produk',
                    'Qty',
                    'Omset Kotor (Rp)',
                    'Refund (Rp)',
                    'Biaya Platform (Rp)',
                    'Biaya Gratis Ongkir (Rp)',
                    'Biaya Layanan (Rp)',
                    'Biaya Promosi (Rp)',
                    'Biaya Lainnya (Rp)',
                    'Total Potongan (Rp)',
                    'Dana Dilepas Net (Rp)',
                    'HPP (Rp)',
                    'Margin (Rp)',
                    'Margin %',
                    'Status'
                ]);

                foreach ($detailData['transactions'] as $idx => $row) {
                    $rawRef = (string) ($row['ref'] ?? '');
                    if (str_contains(strtolower($row['channel'] ?? ''), 'tiktok')) {
                        $formattedRef = str_starts_with($rawRef, "'") ? $rawRef : "'" . $rawRef;
                    } else {
                        $formattedRef = (is_numeric($rawRef) && strlen($rawRef) > 10) ? '="' . $rawRef . '"' : $rawRef;
                    }

                    fputcsv($file, [
                        $idx + 1,
                        $row['order_date'],
                        $row['released_date'],
                        $formattedRef,
                        $row['channel'],
                        $row['customer'],
                        $row['items_summary'],
                        $row['total_qty'],
                        (int)$row['omset'],
                        (int)($row['refund'] ?? 0),
                        (int)$row['platform_fee'],
                        (int)$row['free_shipping_fee'],
                        (int)$row['service_fee'],
                        (int)$row['promo_fee'],
                        (int)$row['other_fee'],
                        (int)($row['total_fee'] ?? 0),
                        (int)$row['net_released'],
                        (int)($row['hpp'] ?? 0),
                        (int)($row['margin_rp'] ?? 0),
                        ($row['margin_pct'] ?? 0) . '%',
                        $row['status']
                    ]);
                }

                fputcsv($file, [
                    '',
                    'TOTAL REKAPITULASI',
                    '',
                    '',
                    '',
                    '',
                    '',
                    $detailData['grandTotalQty'],
                    (int)$detailData['grandTotalOmset'],
                    (int)($detailData['grandTotalRefund'] ?? 0),
                    (int)($detailData['grandTotalPlatformFee'] ?? 0),
                    (int)($detailData['grandTotalFreeShipping'] ?? 0),
                    (int)($detailData['grandTotalServiceFee'] ?? 0),
                    (int)($detailData['grandTotalPromoFee'] ?? 0),
                    (int)($detailData['grandTotalOtherFee'] ?? 0),
                    (int)($detailData['grandTotalTotalFee'] ?? 0),
                    (int)($detailData['grandTotalNetReleased'] ?? 0),
                    (int)($detailData['grandTotalHpp'] ?? 0),
                    (int)($detailData['grandTotalMarginRp'] ?? 0),
                    ($detailData['grandOverallMarginPct'] ?? 0) . '%',
                    ''
                ]);

                fclose($file);
            };
        }

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Cetak Laporan Rekap Penjualan (Semua Status) - V2
     */
    public function printSalesReport(Request $request)
    {
        $tenantId       = Auth::user()->tenant_id;
        $dateFrom       = $request->input('date_from', date('Y-m-01'));
        $dateTo         = $request->input('date_to', date('Y-m-d'));
        $categoryId     = $request->input('category_id');
        $brandId        = $request->input('brand_id');
        $isBundle       = $request->input('is_bundle');
        $isPo           = $request->input('po_status');
        $storeId        = $request->input('store_id');
        $channelCode    = $request->input('channel_code', 'all');
        $customerCat    = $request->input('customer_category', 'all');
        $dropshipFilter = $request->input('is_dropship', 'all');
        $statusFilter   = $request->input('status', 'all');
        $reportFormat   = $request->input('report_format', 'per_produk');
        $search         = $request->input('search');
        $hideZeroSales  = $request->boolean('hide_zero_sales');

        if ($dropshipFilter === '1') {
            $customerCat = 'dropship';
        } elseif ($dropshipFilter === '0') {
            $customerCat = 'umum';
        }

        $customerCategoryLabels = Customer::CATEGORIES;

        if ($reportFormat === 'per_channel') {
            $data = $this->getSalesReportPerChannelData($tenantId, $dateFrom, $dateTo, $channelCode, $customerCat, $statusFilter, $storeId);
            return view('v2.laporan.print_sales_report_channel', array_merge($data, compact('dateFrom', 'dateTo', 'customerCat')));
        } elseif ($reportFormat === 'detail') {
            $data = $this->getSalesReportDetailData($tenantId, $dateFrom, $dateTo, $categoryId, $brandId, $channelCode, $customerCat, $statusFilter, $search, $isBundle, $isPo, $storeId);
            return view('v2.laporan.print_sales_report_detail', array_merge($data, compact('dateFrom', 'dateTo')));
        } elseif ($reportFormat === 'per_tanggal') {
            $data = $this->getSalesReportPerDateData($tenantId, $dateFrom, $dateTo, $channelCode, $customerCat, $statusFilter, $storeId);
            return view('v2.laporan.print_sales_report_date', array_merge($data, compact('dateFrom', 'dateTo')));
        } elseif ($reportFormat === 'per_kategori_pelanggan') {
            $data = $this->getSalesReportPerCustomerCategoryData($tenantId, $dateFrom, $dateTo, $channelCode, $statusFilter);
            return view('v2.laporan.print_sales_report_customer_category', array_merge($data, compact('dateFrom', 'dateTo', 'customerCategoryLabels')));
        } else {
            // Default: per_produk
            $data = $this->getSalesReportData($tenantId, $dateFrom, $dateTo, $categoryId, $brandId, $channelCode, $customerCat, $statusFilter, $search, $hideZeroSales, $isBundle, $isPo, $storeId);
            return view('v2.laporan.print_sales_report', array_merge($data, compact('dateFrom', 'dateTo')));
        }
    }

    /**
     * Export CSV Laporan Rekap Penjualan (Semua Status) - V2
     */
    public function exportSalesReport(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $dateFrom       = $request->input('date_from', date('Y-m-01'));
        $dateTo         = $request->input('date_to', date('Y-m-d'));
        $categoryId     = $request->input('category_id');
        $brandId        = $request->input('brand_id');
        $isBundle       = $request->input('is_bundle');
        $isPo           = $request->input('po_status');
        $storeId        = $request->input('store_id');
        $channelCode    = $request->input('channel_code', 'all');
        $customerCat    = $request->input('customer_category', 'all');
        $statusFilter   = $request->input('status', 'all');
        $search         = $request->input('search');
        $hideZeroSales  = $request->boolean('hide_zero_sales');

        $data = $this->getSalesReportData($tenantId, $dateFrom, $dateTo, $categoryId, $brandId, $channelCode, $customerCat, $statusFilter, $search, $hideZeroSales, $isBundle, $isPo, $storeId);

        $filename = "Laporan_Penjualan_Produk_" . date('Ymd_His') . ".csv";
        $headers = [
            "Content-Type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, ['SKU', 'Nama Produk', 'Kategori', 'Brand', 'Stok Fisik', 'Qty Offline POS', 'Qty Online MP', 'Total Qty Terjual', 'HPP Modal (Rp)', 'Total Omset (Rp)', 'Total HPP (Rp)', 'Laba Kotor (Rp)', 'Margin (%)']);

            foreach ($data['items'] as $item) {
                fputcsv($file, [
                    $item['sku'],
                    $item['name'],
                    $item['category_name'],
                    $item['brand_name'],
                    $item['stock'],
                    $item['qty_offline'],
                    $item['qty_online'],
                    $item['qty_total'],
                    (int)$item['cost_price'],
                    (int)$item['total_omset'],
                    (int)$item['total_hpp'],
                    (int)$item['gross_profit'],
                    round($item['profit_margin'], 2) . '%'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
