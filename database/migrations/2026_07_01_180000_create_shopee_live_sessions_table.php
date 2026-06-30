<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopee_live_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('store_id');
            $table->string('title');
            $table->string('host_name');
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->string('status')->default('live')->comment('live, completed');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
            $table->index(['tenant_id', 'status']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('shopee_live_session_id')->nullable()->after('tiktok_live_session_id');
            $table->foreign('shopee_live_session_id')->references('id')->on('shopee_live_sessions')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['shopee_live_session_id']);
            $table->dropColumn('shopee_live_session_id');
        });

        Schema::dropIfExists('shopee_live_sessions');
    }
};
