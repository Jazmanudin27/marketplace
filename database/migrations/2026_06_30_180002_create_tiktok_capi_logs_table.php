<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tiktok_capi_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('ads_account_id')->nullable();
            $table->string('event_id')->nullable()->comment('UUID unik per event');
            $table->string('status')->default('pending')->comment('pending, sent, failed');
            $table->integer('http_status')->nullable();
            $table->json('response_body')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'order_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tiktok_capi_logs');
    }
};
