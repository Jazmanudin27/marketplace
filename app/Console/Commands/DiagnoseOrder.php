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

            // 1. Cek apakah ada Master Product yang memiliki SKU persis sama dengan SKU pesanan
            $mpByExactSku = null;
            if (!empty($it->sku)) {
                $skuTrim = trim($it->sku);
                $mpByExactSku = MasterProduct::where('tenant_id', $order->tenant_id)
                    ->where(function($q) use ($skuTrim) {
                        $q->where('sku', $skuTrim)
                          ->orWhere(MasterProduct::raw('LOWER(TRIM(sku))'), strtolower($skuTrim));
                    })->first();
            }

            // 2. Cek relasi master_product_id saat ini
            $mpCurrent = null;
            if ($it->master_product_id) {
                $mpCurrent = MasterProduct::where('tenant_id', $order->tenant_id)->find($it->master_product_id);
            }

            $this->line("  --- Analisa SKU & Master Product ---");
            if ($mpByExactSku) {
                $this->info("  [1] Master Product dengan SKU PERSIS ('{$it->sku}'):");
                $this->info("      ID #{$mpByExactSku->id} | SKU: {$mpByExactSku->sku} | HPP: Rp " . number_format($mpByExactSku->cost_price, 0, ',', '.') . " | Nama: {$mpByExactSku->name}");
            } else {
                $this->warn("  [1] TIDAK ADA Master Product dengan SKU '{$it->sku}' di Tenant {$order->tenant_id}.");
            }

            if ($mpCurrent) {
                $this->info("  [2] Master Product yang saat ini TERHUBUNG (ID #{$mpCurrent->id}):");
                $this->info("      SKU Master: {$mpCurrent->sku} | HPP: Rp " . number_format($mpCurrent->cost_price, 0, ',', '.') . " | Nama: {$mpCurrent->name}");
            }

            // Tentukan Master Product mana yang akan dipakai
            // Prioritas: Master Product dengan SKU persis (jika ada dan punya HPP), atau MP saat ini jika punya HPP
            $mp = null;
            $matchMethod = '';

            if ($mpByExactSku && (float)$mpByExactSku->cost_price > 0) {
                $mp = $mpByExactSku;
                $matchMethod = "SKU Persis ('{$mpByExactSku->sku}')";
            } elseif ($mpCurrent && (float)$mpCurrent->cost_price > 0) {
                $mp = $mpCurrent;
                $matchMethod = "Relasi ID (#{$mpCurrent->id})";
            } elseif ($mpByExactSku) {
                $mp = $mpByExactSku;
                $matchMethod = "SKU Persis ('{$mpByExactSku->sku}') (HPP masih 0)";
            } elseif ($mpCurrent) {
                $mp = $mpCurrent;
                $matchMethod = "Relasi ID (#{$mpCurrent->id}) (HPP masih 0)";
            }

            if ($mp) {
                $this->info("  -> Terpilih: Master ID #{$mp->id} ({$matchMethod})");
                $this->info("     HPP (cost_price): Rp " . number_format($mp->cost_price, 0, ',', '.'));

                if ((float)$mp->cost_price <= 0) {
                    $this->warn("     PERINGATAN: cost_price di Master Product ini bernilai 0 di menu Master Produk ERP!");
                    
                    // Cek produk serupa yang memiliki cost_price > 0
                    $similar = MasterProduct::where('tenant_id', $order->tenant_id)
                        ->where('cost_price', '>', 0)
                        ->where(function($q) use ($it, $mp) {
                            $words = explode(' ', trim($it->product_name));
                            foreach (array_slice($words, 0, 3) as $w) {
                                if (strlen($w) > 3) $q->where('name', 'LIKE', "%{$w}%");
                            }
                        })
                        ->take(3)
                        ->get();
                    if ($similar->isNotEmpty()) {
                        $this->line("     Contoh produk serupa yang punya HPP:");
                        foreach ($similar as $sm) {
                            $this->line("       - ID #{$sm->id} [{$sm->sku}] Rp " . number_format($sm->cost_price, 0, ',', '.') . " : {$sm->name}");
                        }
                    }
                } else {
                    $qty = max(1, (int)$it->quantity);
                    $newSubtotal = (float)$mp->cost_price * $qty;

                    if ($shouldFix) {
                        $it->master_product_id = $mp->id;
                        $it->cost_price = (float)$mp->cost_price;
                        $it->hpp_subtotal = $newSubtotal;
                        $it->save();

                        // Update juga mapping di marketplace_products jika ada
                        if ($it->marketplace_product_id) {
                            MarketplaceProduct::where('id', $it->marketplace_product_id)
                                ->orWhere('marketplace_product_id', $it->marketplace_product_id)
                                ->update(['master_product_id' => $mp->id]);
                        }

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
