<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class FixTiktokPendingStockDeductions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tiktok:fix-stock-deduction {--order_id= : ID pesanan tertentu}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Memproses potongan stok dan mutasi kartu stok untuk pesanan TikTok yang tertahan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai pengecekan dan perbaikan potongan stok pesanan TikTok...');

        $orderIdOption = $this->option('order_id');

        $query = Order::where('is_stock_deducted', false)
            ->where('order_status', '!=', Order::STATUS_CANCELLED)
            ->whereHas('store.channel', function ($q) {
                $q->whereIn('code', ['tiktok', 'tokopedia']);
            });

        if ($orderIdOption) {
            $query->where('id', $orderIdOption);
        }

        $pendingOrders = $query->get();

        if ($pendingOrders->isEmpty()) {
            $this->info('Tidak ada pesanan TikTok yang tertahan potongan stoknya.');
            return 0;
        }

        $this->info("Ditemukan {$pendingOrders->count()} pesanan TikTok yang belum terpotong stoknya. Memproses...");

        $successCount = 0;
        $failedCount = 0;

        foreach ($pendingOrders as $order) {
            try {
                $order->processStockDeduction();
                $order->refresh();

                if ($order->is_stock_deducted) {
                    $successCount++;
                    $this->info("✅ Pesanan #{$order->id} ({$order->order_marketplace_id}) berhasil dipotong stoknya & dicatat ke Kartu Stok.");
                } else {
                    $failedCount++;
                    $this->warn("⚠️ Pesanan #{$order->id} ({$order->order_marketplace_id}) sebagian item belum dapat dipetakan ke MasterProduct.");
                }
            } catch (\Exception $e) {
                $failedCount++;
                $this->error("❌ Gagal memproses pesanan #{$order->id}: " . $e->getMessage());
                Log::error("[TikTok Fix] Error processing order #{$order->id}: " . $e->getMessage());
            }
        }

        $this->info("Proses selesai: {$successCount} sukses dipotong, {$failedCount} perlu pemeriksaan rincian item.");
        return 0;
    }
}
