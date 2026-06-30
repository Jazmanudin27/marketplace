<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\BudgetRuleService;
use Illuminate\Console\Command;

class EvaluateBudgetRules extends Command
{
    protected $signature = 'ads:evaluate-rules {--tenant= : ID tenant tertentu}';
    protected $description = 'Evaluasi aturan budget/ROAS iklan dan buat alert jika terpicu';

    public function handle(BudgetRuleService $service)
    {
        $tenantId = $this->option('tenant');

        if ($tenantId) {
            $this->info("Mengevaluasi budget rules untuk Tenant #{$tenantId}...");
            $triggered = $service->evaluateAll((int) $tenantId);
            $this->info("Selesai! {$triggered} alert baru terpicu.");
            return 0;
        }

        $tenants = Tenant::all();
        $totalTriggered = 0;

        foreach ($tenants as $tenant) {
            $this->info("Mengevaluasi budget rules untuk Tenant #{$tenant->id} ({$tenant->name})...");
            $triggered = $service->evaluateAll($tenant->id);
            $totalTriggered += $triggered;
        }

        $this->info("Evaluasi global selesai! Total {$totalTriggered} alert terpicu di semua tenant.");
        return 0;
    }
}
