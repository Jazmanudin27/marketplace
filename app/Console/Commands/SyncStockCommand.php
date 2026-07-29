<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MarketplaceProduct;
use App\Models\MasterProduct;
use App\Jobs\PushStockToMarketplaces;

class SyncStockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:sync 
                            {--filter=diff : Filter status (diff|match|all|po|nomap)}
                            {--store_id= : ID Toko tertentu (opsional)}
                            {--channel= : Code channel/marketplace shopee|tiktok|tokopedia|lazada (opsional)}
                            {--tenant_id= : ID Tenant tertentu (opsional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi stok dari Master Product ERP ke Marketplace via terminal CLI';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filter   = $this->option('filter') ?? 'diff';
        $storeId  = $this->option('store_id');
        $channel  = $this->option('channel');
        $tenantId = $this->option('tenant_id');

        $this->info("Menjalankan sinkronisasi stok marketplace (Filter: {$filter})...");

        $query = MarketplaceProduct::whereHas('store', function($q) use ($tenantId) {
                $q->where('status', 'connected');
                if ($tenantId) {
                    $q->where('tenant_id', $tenantId);
                }
            })
            ->where('marketplace_products.sync_stock', true)
            ->whereNotNull('marketplace_products.master_product_id');

        if ($storeId) {
            $query->where('marketplace_products.store_id', $storeId);
        }

        if ($channel) {
            $query->whereHas('store.channel', function($q) use ($channel) {
                $q->where('code', $channel);
            });
        }

        if ($filter === 'diff') {
            $query->join('master_products', 'marketplace_products.master_product_id', '=', 'master_products.id')
                  ->where('marketplace_products.is_pre_order', false)
                  ->where('master_products.is_preorder', false)
                  ->where('marketplace_products.name', 'not like', '%PRE ORDER%')
                  ->where('marketplace_products.name', 'not like', '%PREORDER%')
                  ->where('marketplace_products.name', 'not like', '%PRE-ORDER%')
                  ->where('marketplace_products.name', 'not like', 'PO %')
                  ->where('marketplace_products.name', 'not like', '% PO %')
                  ->whereRaw('marketplace_products.stock != IF(master_products.stock - COALESCE(marketplace_products.safety_stock, 0) < 0, 0, master_products.stock - COALESCE(marketplace_products.safety_stock, 0))');
        } elseif ($filter === 'match') {
            $query->join('master_products', 'marketplace_products.master_product_id', '=', 'master_products.id')
                  ->where('marketplace_products.is_pre_order', false)
                  ->where('master_products.is_preorder', false)
                  ->where('marketplace_products.name', 'not like', '%PRE ORDER%')
                  ->where('marketplace_products.name', 'not like', '%PREORDER%')
                  ->where('marketplace_products.name', 'not like', '%PRE-ORDER%')
                  ->where('marketplace_products.name', 'not like', 'PO %')
                  ->where('marketplace_products.name', 'not like', '% PO %')
                  ->whereRaw('marketplace_products.stock = IF(master_products.stock - COALESCE(marketplace_products.safety_stock, 0) < 0, 0, master_products.stock - COALESCE(marketplace_products.safety_stock, 0))');
        }

        $masterProductIds = $query->distinct()->pluck('marketplace_products.master_product_id')->filter()->toArray();

        if (empty($masterProductIds)) {
            $this->warn("Tidak ada produk yang perlu disinkronkan sesuai kriteria filter.");
            return 0;
        }

        $masterProducts = MasterProduct::whereIn('id', $masterProductIds)->get(['id', 'sku', 'stock']);
        $bar = $this->output->createProgressBar(count($masterProducts));
        $bar->start();

        $count = 0;
        foreach ($masterProducts as $mp) {
            PushStockToMarketplaces::dispatch($mp->id, $mp->stock);
            $count++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Berhasil mengirim instruksi push stok untuk {$count} Master Product ke antrean.");

        return 0;
    }
}
