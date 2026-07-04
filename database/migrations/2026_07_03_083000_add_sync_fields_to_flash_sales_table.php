<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flash_sales', function (Blueprint $table) {
            $table->boolean('is_synced')->default(false)->after('status');
            $table->dateTime('last_synced_at')->nullable()->after('is_synced');
            $table->text('sync_notes')->nullable()->after('last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('flash_sales', function (Blueprint $table) {
            $table->dropColumn(['is_synced', 'last_synced_at', 'sync_notes']);
        });
    }
};
