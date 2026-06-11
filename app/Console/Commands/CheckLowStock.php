<?php

namespace App\Console\Commands;

use App\Models\MasterProduct;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckLowStock extends Command
{
    protected $signature = 'stock:check-low {--tenant= : ID tenant tertentu (opsional)}';
    protected $description = 'Cek produk dengan stok menipis (stock <= min_stock) dan kirim alert.';

    public function handle(): int
    {
        $this->info('🔍 Memeriksa stok menipis...');

        $query = MasterProduct::with('tenant')
            ->whereColumn('stock', '<=', 'min_stock')
            ->where('is_active', true)
            ->orderBy('stock');

        if ($tenantId = $this->option('tenant')) {
            $query->where('tenant_id', $tenantId);
        }

        $lowStockProducts = $query->get();

        if ($lowStockProducts->isEmpty()) {
            $this->info('✅ Tidak ada produk dengan stok menipis.');
            return self::SUCCESS;
        }

        $this->warn("⚠️  Ditemukan {$lowStockProducts->count()} produk dengan stok menipis:");
        $this->table(
            ['Tenant', 'SKU', 'Nama Produk', 'Stok', 'Min. Stok'],
            $lowStockProducts->map(fn($p) => [
                $p->tenant?->name ?? '-',
                $p->sku,
                $p->name,
                $p->stock,
                $p->min_stock,
            ])
        );

        // Log ke Laravel log system
        foreach ($lowStockProducts as $product) {
            Log::warning('[StockAlert] Stok menipis', [
                'tenant_id'   => $product->tenant_id,
                'product_id'  => $product->id,
                'sku'         => $product->sku,
                'name'        => $product->name,
                'stock'       => $product->stock,
                'min_stock'   => $product->min_stock,
            ]);
        }

        $this->info('📋 Alert telah dicatat ke log. Kembangkan ke email/Slack di sini.');
        return self::SUCCESS;
    }
}
