<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\AutoAttributionService;
use Illuminate\Console\Command;

class AutoAttributeOrders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'marketing:auto-attribute
                            {--tenant= : ID tenant spesifik (kosong = semua tenant)}
                            {--limit=200 : Jumlah maksimum order yang diproses per tenant}
                            {--dry-run : Preview tanpa menyimpan perubahan}';

    /**
     * The console command description.
     */
    protected $description = 'Atribusikan order yang belum terhubung ke campaign iklan secara otomatis (3-layer: UTM → Store Default → Platform Match)';

    public function handle(AutoAttributionService $service): int
    {
        $tenantId = $this->option('tenant');
        $limit    = (int) $this->option('limit');
        $dryRun   = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE — tidak ada perubahan yang disimpan.');
        }

        $this->info('🚀 Memulai Auto-Attribution Pesanan ke Campaign Iklan...');

        // Tentukan tenant yang akan diproses
        if ($tenantId) {
            $tenants = Tenant::where('id', $tenantId)->get();
        } else {
            $tenants = Tenant::all();
        }

        if ($tenants->isEmpty()) {
            $this->warn('Tidak ada tenant ditemukan.');
            return self::SUCCESS;
        }

        $grandTotal      = 0;
        $grandAttributed = 0;
        $grandSkipped    = 0;

        foreach ($tenants as $tenant) {
            $this->line('');
            $this->info("🏢 Tenant: {$tenant->name} (ID: {$tenant->id})");

            $result = $service->attributeBatch($tenant->id, $limit, $dryRun);

            $grandTotal      += $result['total'];
            $grandAttributed += $result['attributed'];
            $grandSkipped    += $result['skipped'];

            if ($result['total'] === 0) {
                $this->line('  ✅ Tidak ada order baru yang perlu diproses.');
                continue;
            }

            // Tampilkan tabel hasil
            $rows = collect($result['results'])->map(fn($r) => [
                $r['invoice_number'] ?? $r['order_id'],
                $r['attributed'] ? '✅ Ya' : '❌ Tidak',
                $r['layer']  ? "Layer {$r['layer']}" : '-',
                $r['reason'],
            ])->toArray();

            $this->table(
                ['Invoice', 'Teratribusi', 'Layer', 'Alasan'],
                $rows
            );

            $this->info(
                "  Hasil: {$result['total']} diproses | " .
                "{$result['attributed']} teratribusi | " .
                "{$result['skipped']} dilewati"
            );
        }

        $this->line('');
        $this->info('═══════════════════════════════════════════════════');
        $this->info("📊 TOTAL: {$grandTotal} order diproses");
        $this->info("   ✅ Teratribusi : {$grandAttributed}");
        $this->info("   ⏭️  Dilewati    : {$grandSkipped}");
        $this->info('═══════════════════════════════════════════════════');

        if ($dryRun) {
            $this->warn('ℹ️  Ini adalah DRY RUN — tidak ada data yang berubah.');
        }

        return self::SUCCESS;
    }
}
