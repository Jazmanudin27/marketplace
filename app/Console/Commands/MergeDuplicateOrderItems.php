<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MergeDuplicateOrderItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:merge-duplicate-items 
                            {--order= : Nomor invoice atau order marketplace ID tertentu}
                            {--days=60 : Batas mundur pencarian pesanan (hari)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menggabungkan baris item produk yang kembar/identik SKU-nya dalam 1 pesanan menjadi 1 baris akumulasi quantity';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orderQuery = $this->option('order');
        $days = (int) ($this->option('days') ?: 60);

        $this->info("🔍 Memulai pemindaian item pesanan kembar (SKU duplikat)...");

        $query = Order::query()->with('items');

        if (!empty($orderQuery)) {
            $query->where(function ($q) use ($orderQuery) {
                $q->where('order_marketplace_id', $orderQuery)
                  ->orWhere('invoice_number', $orderQuery)
                  ->orWhere('tracking_number', $orderQuery);
            });
        } else {
            $query->where('created_at', '>=', now()->subDays($days));
        }

        $orders = $query->get();

        if ($orders->isEmpty()) {
            $this->warn("⚠️ Tidak ada pesanan yang sesuai kriteria.");
            return 0;
        }

        $totalMergedOrders = 0;
        $totalRemovedRows = 0;

        foreach ($orders as $order) {
            $items = $order->items;
            if ($items->count() <= 1) {
                continue;
            }

            // Kelompokkan berdasarkan master_product_id atau sku
            $groups = [];
            foreach ($items as $item) {
                $key = $item->master_product_id 
                    ? 'm_' . $item->master_product_id 
                    : 'sku_' . trim((string)($item->sku ?: $item->sku_id));
                $groups[$key][] = $item;
            }

            $orderModified = false;

            DB::beginTransaction();
            try {
                foreach ($groups as $groupKey => $groupItems) {
                    if (count($groupItems) <= 1) {
                        continue;
                    }

                    // Ambil item pertama sebagai penampung
                    $primary = $groupItems[0];
                    $addQty = 0;
                    $addTotalPrice = 0;
                    $addSellerDiscount = 0;
                    $addPlatformDiscount = 0;
                    $addHppSubtotal = 0;

                    for ($i = 1; $i < count($groupItems); $i++) {
                        $other = $groupItems[$i];
                        $addQty += (int) $other->quantity;
                        $addTotalPrice += (float) $other->total_price;
                        $addSellerDiscount += (float) $other->seller_discount;
                        $addPlatformDiscount += (float) $other->platform_discount;
                        $addHppSubtotal += (float) $other->hpp_subtotal;

                        $other->delete();
                        $totalRemovedRows++;
                        $orderModified = true;
                    }

                    $newTotalQty = (int) $primary->quantity + $addQty;
                    $newTotalPrice = (float) $primary->total_price + $addTotalPrice;

                    $primary->update([
                        'quantity'          => $newTotalQty,
                        'total_price'       => $newTotalPrice,
                        'seller_discount'   => (float) $primary->seller_discount + $addSellerDiscount,
                        'platform_discount' => (float) $primary->platform_discount + $addPlatformDiscount,
                        'hpp_subtotal'      => (float) $primary->hpp_subtotal + $addHppSubtotal,
                        'price'             => $newTotalQty > 0 ? round($newTotalPrice / $newTotalQty, 2) : $primary->price,
                    ]);
                }

                DB::commit();

                if ($orderModified) {
                    $totalMergedOrders++;
                    $this->info("✅ Pesanan [{$order->order_marketplace_id}] berhasil digabungkan item kembar.");
                }
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->error("❌ Gagal memproses order [{$order->order_marketplace_id}]: " . $e->getMessage());
            }
        }

        $this->info("\n🎉 Selesai!");
        $this->info("   └─ Total Pesanan Dibereskan: {$totalMergedOrders}");
        $this->info("   └─ Total Baris Duplikat Dihapus: {$totalRemovedRows}");

        return 0;
    }
}
