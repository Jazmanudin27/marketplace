<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('return_orders', function (Blueprint $table) {
            $table->string('return_tracking_number')->nullable()->after('return_sn');
            $table->string('shipping_provider')->nullable()->after('return_tracking_number');
            $table->foreignId('checked_by')->nullable()->after('is_restocked')->constrained('users')->nullOnDelete();
        });

        Schema::table('return_order_items', function (Blueprint $table) {
            $table->string('inspection_status')->default('PENDING')->after('quantity');
            $table->text('inspection_notes')->nullable()->after('inspection_status');
        });

        // Copy existing QC results from return_orders to return_order_items (for backward compatibility)
        try {
            $returnOrders = DB::table('return_orders')->get();
            foreach ($returnOrders as $ro) {
                // If it has already been restocked, copy the main status/notes to all its items
                if ($ro->is_restocked) {
                    $inspectionStatus = isset($ro->inspection_status) ? $ro->inspection_status : 'GOOD';
                    $inspectionNotes = isset($ro->inspection_notes) ? $ro->inspection_notes : null;
                    
                    DB::table('return_order_items')
                        ->where('return_order_id', $ro->id)
                        ->update([
                            'inspection_status' => $inspectionStatus,
                            'inspection_notes' => $inspectionNotes,
                        ]);
                }
            }
        } catch (\Throwable $e) {
            // Ignore during setup/testing
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('return_orders', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['checked_by']);
            }
            $table->dropColumn(['return_tracking_number', 'shipping_provider', 'checked_by']);
        });

        Schema::table('return_order_items', function (Blueprint $table) {
            $table->dropColumn(['inspection_status', 'inspection_notes']);
        });
    }
};
