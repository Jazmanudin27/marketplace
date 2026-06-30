<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TiktokAudienceService;
use Illuminate\Console\Command;

class SyncTiktokAudiences extends Command
{
    protected $signature = 'ads:sync-tiktok-audiences {--tenant= : ID tenant tertentu}';
    protected $description = 'Sinkronisasi data pembeli marketplace ke TikTok Custom Audience';

    public function handle(TiktokAudienceService $service)
    {
        $tenantId = $this->option('tenant');

        if ($tenantId) {
            $this->info("Mensinkronisasi TikTok audiences untuk Tenant #{$tenantId}...");
            $synced = $service->syncAll((int) $tenantId);
            $this->info("Selesai! Berhasil mensinkronisasi {$synced} custom audiences.");
            return 0;
        }

        $tenants = Tenant::all();
        $totalSynced = 0;

        foreach ($tenants as $tenant) {
            $this->info("Mensinkronisasi TikTok audiences untuk Tenant #{$tenant->id} ({$tenant->name})...");
            $synced = $service->syncAll($tenant->id);
            $totalSynced += $synced;
        }

        $this->info("Sinkronisasi Custom Audience selesai! Total {$totalSynced} custom audiences ter-update.");
        return 0;
    }
}
