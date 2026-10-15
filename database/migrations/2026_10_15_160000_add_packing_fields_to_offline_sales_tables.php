<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('offline_sales')) {
            Schema::table('offline_sales', function (Blueprint $table) {
                if (!Schema::hasColumn('offline_sales', 'packing_status')) {
                    $table->string('packing_status', 30)->default('pending')->after('status');
                }
                if (!Schema::hasColumn('offline_sales', 'packed_at')) {
                    $table->timestamp('packed_at')->nullable()->after('packing_status');
                }
                if (!Schema::hasColumn('offline_sales', 'packed_by')) {
                    $table->unsignedBigInteger('packed_by')->nullable()->after('packed_at');
                    $table->foreign('packed_by')->references('id')->on('users')->onDelete('set null');
                }
            });
        }

        if (Schema::hasTable('offline_sale_items')) {
            Schema::table('offline_sale_items', function (Blueprint $table) {
                if (!Schema::hasColumn('offline_sale_items', 'packed_quantity')) {
                    $table->integer('packed_quantity')->default(0)->after('quantity');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('offline_sales')) {
            Schema::table('offline_sales', function (Blueprint $table) {
                if (Schema::hasColumn('offline_sales', 'packed_by')) {
                    $table->dropForeign(['packed_by']);
                    $table->dropColumn('packed_by');
                }
                if (Schema::hasColumn('offline_sales', 'packed_at')) {
                    $table->dropColumn('packed_at');
                }
                if (Schema::hasColumn('offline_sales', 'packing_status')) {
                    $table->dropColumn('packing_status');
                }
            });
        }

        if (Schema::hasTable('offline_sale_items')) {
            Schema::table('offline_sale_items', function (Blueprint $table) {
                if (Schema::hasColumn('offline_sale_items', 'packed_quantity')) {
                    $table->dropColumn('packed_quantity');
                }
            });
        }
    }
};
