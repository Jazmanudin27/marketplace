<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('orders')
            ->where('is_printed', true)
            ->where(function ($query) {
                $query->whereNull('tracking_number')
                      ->orWhere('tracking_number', '')
                      ->orWhere('tracking_number', '-');
            })
            ->update([
                'is_printed' => false,
                'printed_at' => null,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak perlu rollback karena pesanan tanpa resi memang tidak valid berstatus sudah dicetak
    }
};
