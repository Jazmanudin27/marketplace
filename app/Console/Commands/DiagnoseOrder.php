<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\MasterProduct;
use App\Models\MarketplaceProduct;

class DiagnoseOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:diagnose {ref : Nomor pesanan / order_marketplace_id} {--fix : Otomatis perbaiki jika ditemukan Master Product}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mendiagnosa detail pesanan dan penyebab HPP 0 serta memperbaikinya';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ref = trim($this->argument('ref'));
        $shouldFix = $this->option('fix') !== false; // Default fix true

        $this->info("=== DIAGNOSA PESANAN: {$ref} ===");

        $query = Order::with(['items', 'store.channel'])
            ->where('order_marketplace_id', $ref)
            ->orWhere('invoice_number', $ref);

        if (is_numeric($ref)) {
            $query->orWhere('id', (int)$ref);
        }

        $order = $query->first();

        if (!$order) {
            $this->error("Pesanan dengan referensi '{$ref}' tidak ditemukan di database.");
            return 1;
        }

        $this->line("ID Database          : {$order->id}");
        $this->line("Marketplace ID       : {$order->order_marketplace_id}");
        $this->line("Invoice Number       : {$order->invoice_number}");
        $this->line("Tenant ID            : {$order->tenant_id}");
        $this->line("Store                : " . ($order->store->store_name ?? '-') . " (" . ($order->store->channel->name ?? '-') . ")");
        $this->line("Status Pesanan       : {$order->order_status}");
        $this->line("Tanggal Order        : {$order->order_date}");
        $this->line("Tanggal Selesai      : " . ($order->completed_at ?: 'NULL (BELUM DILEPAS)'));
        $this->line("Omset (Total Amount) : Rp " . number_format($order->total_amount, 0, ',', '.'));
        $this->line("Refund Amount        : Rp " . number_format($order->refund_amount, 0, ',', '.'));
        $this->line("Dana Cair (Net Amt)  : Rp " . number_format($order->net_amount, 0, ',', '.'));
        $this->line("HPP Total (Accessor) : Rp " . number_format($order->hpp_total, 0, ',', '.'));

        $this->info("\n--- RINCIAN ITEM PESANAN (" . $order->items->count() . " Item) ---");

        $fixedCount = 0;

        foreach ($order->items as $idx => $it) {
            $num = $idx + 1;
            $this->line("\n[Item #{$num}]");
            $this->line("  ID Item              : {$it->id}");
            $this->line("  Nama Produk          : {$it->product_name}");
            $this->line("  SKU                  : " . ($it->sku ?: '(kosong)'));
            $this->line("  Seller SKU           : " . ($it->seller_sku ?: '(kosong)'));
            $this->line("  SKU ID (Model ID)    : " . ($it->sku_id ?: '(kosong)'));
            $this->line("  Mkt Product ID       : " . ($it->marketplace_product_id ?: '(kosong)'));
            $this->line("  Master Product ID    : " . ($it->master_product_id ?: '(NULL - Belum Terhubung)'));
            $this->line("  Qty                  : {$it->quantity}");
            $this->line("  Item Cost Price      : Rp " . number_format($it->cost_price, 0, ',', '.'));
            $this->line("  Item HPP Subtotal    : Rp " . number_format($it->hpp_subtotal, 0, ',', '.'));

            // Analisa pencarian Master Product
            $mp = null;
            $matchMethod = '';

            // 1. Cek by master_product_id
            if ($it->master_product_id) {
                $mp = MasterProduct::where('tenant_id', $order->tenant_id)->find($it->master_product_id);
                if ($mp) $matchMethod = "Relasi ID (#{$mp->id})";
            }

            // 2. Cek by SKU
            if (!$mp && !empty($it->sku)) {
                $skuTrim = trim($it->sku);
                $mp = MasterProduct::where('tenant_id', $order->tenant_id)
                    ->where(function($q) use ($skuTrim) {
                        $q->where('sku', $skuTrim)
                          ->orWhere(MasterProduct::raw('LOWER(TRIM(sku))'), strtolower($skuTrim));
                    })->first();
                if ($mp) $matchMethod = "Kecocokan SKU ('{$skuTrim}') -> Master ID #{$mp->id}";
            }

            // 3. Cek by Seller SKU
            if (!$mp && !empty($it->seller_sku)) {
                $sellerSkuTrim = trim($it->seller_sku);
                $mp = MasterProduct::where('tenant_id', $order->tenant_id)
                    ->where(function($q) use ($sellerSkuTrim) {
                        $q->where('sku', $sellerSkuTrim)
                          ->orWhere(MasterProduct::raw('LOWER(TRIM(sku))'), strtolower($sellerSkuTrim));
                    })->first();
                if ($mp) $matchMethod = "Kecocokan Seller SKU ('{$sellerSkuTrim}') -> Master ID #{$mp->id}";
            }

            // 4. Cek via Marketplace Product
            if (!$mp && $it->marketplace_product_id) {
                $mkt = MarketplaceProduct::where('store_id', $order->store_id)
                    ->where(function($q) use ($it) {
                        $q->where('id', $it->marketplace_product_id)
                          ->orWhere('marketplace_product_id', $it->marketplace_product_id);
                        if (!empty($it->sku_id)) {
                            $q->orWhere('marketplace_variant_id', $it->sku_id);
                        }
                    })->first();
                if ($mkt && $mkt->master_product_id) {
                    $mp = MasterProduct::where('tenant_id', $order->tenant_id)->find($mkt->master_product_id);
                    if ($mp) $matchMethod = "Tabel MarketplaceProduct ('{$mkt->name}') -> Master ID #{$mp->id}";
                }
            }

            if ($mp) {
                $this->info("  -> Master Product Ditemukan via {$matchMethod}");
                $this->info("     Nama Master       : {$mp->name}");
                $this->info("     SKU Master        : {$mp->sku}");
                $this->info("     HPP (cost_price)  : Rp " . number_format($mp->cost_price, 0, ',', '.'));

                if ((float)$mp->cost_price <= 0) {
                    $this->warn("     PERINGATAN: Nilai cost_price di Master Product ini adalah 0!");
                } else {
                    $qty = max(1, (int)$it->quantity);
                    $newSubtotal = (float)$mp->cost_price * $qty;

                    if ($shouldFix) {
                        $it->master_product_id = $mp->id;
                        $it->cost_price = (float)$mp->cost_price;
                        $it->hpp_subtotal = $newSubtotal;
                        $it->save();
                        $this->info("     BERHASIL DIPERBAIKI: HPP Subtotal diupdate menjadi Rp " . number_format($newSubtotal, 0, ',', '.'));
                        $fixedCount++;
                    }
                }
            } else {
                $this->error("  -> Master Product TIDAK DITEMUKAN untuk item ini!");
                $this->line("     Saran: Pastikan SKU '{$it->sku}' sudah terdaftar di Master Produk untuk Tenant {$order->tenant_id}.");
            }
        }

        // Refresh dan cek ulang HPP
        $order->refresh();
        $this->info("\n=== HASIL AKHIR ===");
        $this->line("HPP Total Baru: Rp " . number_format($order->hpp_total, 0, ',', '.'));
        $this->line("Item yang diperbaiki: {$fixedCount} item");

        return 0;
    }
}
