<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('marketing_teams', function (Blueprint $table) {
            $table->date('date_from')->nullable()->after('period_year')->comment('Tanggal Awal Acuan Dana Cair');
            $table->date('date_to')->nullable()->after('date_from')->comment('Tanggal Akhir Acuan Dana Cair');
            $table->index(['tenant_id', 'date_from', 'date_to']);
        });

        // Backfill data lama jika ada: jika period_month & period_year terisi, set date_from dan date_to ke 1 s/d akhir bulan tersebut
        $teams = DB::table('marketing_teams')
            ->whereNotNull('period_month')
            ->whereNotNull('period_year')
            ->get();

        foreach ($teams as $team) {
            $month = (int) $team->period_month;
            $year  = (int) $team->period_year;
            if ($month >= 1 && $month <= 12 && $year >= 2020) {
                $dateFrom = sprintf('%04d-%02d-01', $year, $month);
                $dateTo   = date('Y-m-t', strtotime($dateFrom));
                DB::table('marketing_teams')
                    ->where('id', $team->id)
                    ->update([
                        'date_from' => $dateFrom,
                        'date_to'   => $dateTo,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketing_teams', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'date_from', 'date_to']);
            $table->dropColumn(['date_from', 'date_to']);
        });
    }
};
