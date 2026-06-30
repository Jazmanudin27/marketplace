<?php

namespace App\Services;

use App\Models\AdsBudgetRule;
use App\Models\AdsBudgetAlert;
use App\Models\AdsCampaign;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BudgetRuleService
{
    /**
     * Evaluasi semua rules yang aktif untuk tenant tertentu
     */
    public function evaluateAll(int $tenantId): int
    {
        $rules = AdsBudgetRule::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('campaign')
            ->get();

        $triggeredCount = 0;

        foreach ($rules as $rule) {
            try {
                if ($this->evaluate($rule)) {
                    $triggeredCount++;
                }
            } catch (\Throwable $e) {
                Log::error("Gagal mengevaluasi Budget Rule #{$rule->id}: " . $e->getMessage());
            }
        }

        return $triggeredCount;
    }

    /**
     * Evaluasi sebuah rule.
     * Mengembalikan true jika rule terpicu dan alert baru dibuat.
     */
    public function evaluate(AdsBudgetRule $rule): bool
    {
        $campaign = $rule->campaign;
        if (!$campaign) {
            return false;
        }

        // Cek cooldown: jangan buat alert baru jika rule yang sama baru saja terpicu dalam 24 jam terakhir
        if ($rule->last_triggered_at && $rule->last_triggered_at->gt(now()->subDay())) {
            return false;
        }

        $triggered = false;
        $message = '';
        $level = AdsBudgetAlert::LEVEL_WARNING;
        $context = [];

        switch ($rule->condition) {
            case AdsBudgetRule::CONDITION_ROAS_BELOW:
                $actualRoas = $campaign->actual_roas;
                $targetRoas = (float) $rule->threshold_value;
                $spend = $campaign->total_spend;

                // Hanya evaluasi jika sudah ada spend untuk menghindari false warning
                if ($spend > 0 && $actualRoas < $targetRoas) {
                    $triggered = true;
                    $message = "ROAS campaign '{$campaign->name}' saat ini adalah " . number_format($actualRoas, 2) . "x, di bawah target threshold Anda (" . number_format($targetRoas, 2) . "x).";
                    $level = AdsBudgetAlert::LEVEL_CRITICAL;
                    $context = [
                        'actual_roas' => $actualRoas,
                        'threshold_roas' => $targetRoas,
                        'total_spend' => $spend,
                    ];
                }
                break;

            case AdsBudgetRule::CONDITION_ROAS_ABOVE:
                $actualRoas = $campaign->actual_roas;
                $targetRoas = (float) $rule->threshold_value;
                $spend = $campaign->total_spend;

                if ($spend > 0 && $actualRoas > $targetRoas) {
                    $triggered = true;
                    $message = "Selamat! Performa campaign '{$campaign->name}' luar biasa dengan ROAS " . number_format($actualRoas, 2) . "x, di atas target threshold Anda (" . number_format($targetRoas, 2) . "x).";
                    $level = AdsBudgetAlert::LEVEL_INFO;
                    $context = [
                        'actual_roas' => $actualRoas,
                        'threshold_roas' => $targetRoas,
                        'total_spend' => $spend,
                    ];
                }
                break;

            case AdsBudgetRule::CONDITION_SPEND_EXCEEDS_DAILY:
                // Cek spend hari ini
                $todaySpend = (float) $campaign->performanceLogs()
                    ->whereDate('log_date', Carbon::today())
                    ->sum('ad_spend');
                $threshold = (float) $rule->threshold_value;

                if ($todaySpend > $threshold) {
                    $triggered = true;
                    $message = "Pengeluaran harian iklan untuk '{$campaign->name}' hari ini sudah mencapai Rp " . number_format($todaySpend, 0, ',', '.') . ", melebihi batas harian yang ditentukan (Rp " . number_format($threshold, 0, ',', '.') . ").";
                    $level = AdsBudgetAlert::LEVEL_WARNING;
                    $context = [
                        'today_spend' => $todaySpend,
                        'threshold_limit' => $threshold,
                    ];
                }
                break;

            case AdsBudgetRule::CONDITION_SPEND_EXCEEDS_TOTAL:
                $totalSpend = $campaign->total_spend;
                $threshold = (float) $rule->threshold_value;

                if ($totalSpend > $threshold) {
                    $triggered = true;
                    $message = "Total pengeluaran iklan untuk '{$campaign->name}' telah mencapai Rp " . number_format($totalSpend, 0, ',', '.') . ", melebihi batas anggaran total (Rp " . number_format($threshold, 0, ',', '.') . ").";
                    $level = AdsBudgetAlert::LEVEL_CRITICAL;
                    $context = [
                        'total_spend' => $totalSpend,
                        'threshold_limit' => $threshold,
                    ];
                }
                break;
        }

        if ($triggered) {
            // Buat record alert
            AdsBudgetAlert::create([
                'tenant_id' => $rule->tenant_id,
                'ads_campaign_id' => $campaign->id,
                'ads_budget_rule_id' => $rule->id,
                'level' => $level,
                'message' => $message,
                'context' => $context,
                'triggered_at' => now(),
            ]);

            // Update timestamp rule
            $rule->update([
                'last_triggered_at' => now(),
            ]);

            // Kirim WhatsApp alert jika nomor penerima dikonfigurasi
            $recipient = $rule->whatsapp_recipient ?: env('WHATSAPP_ALERT_RECIPIENT');
            if ($recipient) {
                $platform = $campaign->adsAccount ? strtoupper($campaign->adsAccount->platform) : 'MANUAL';
                $waMessage = "⚠️ *ERP ADS BUDGET ALERT* ⚠️\n\n"
                    . "Platform: *" . $platform . "*\n"
                    . "Campaign: *" . $campaign->name . "*\n"
                    . "Rule: *" . $rule->name . "*\n"
                    . "Pesan: " . $message . "\n\n"
                    . "Silakan cek dashboard ERP Anda untuk detail optimasi.";

                try {
                    \App\Services\WhatsAppService::send($recipient, $waMessage);
                } catch (\Throwable $e) {
                    Log::error("Gagal mengirim notifikasi WhatsApp: " . $e->getMessage());
                }
            }

            return true;
        }

        return false;
    }
}
