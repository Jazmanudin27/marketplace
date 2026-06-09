<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('channel_id')->constrained()->onDelete('cascade');
            $table->string('store_name');
            $table->string('marketplace_store_id', 100); // ID toko dari API marketplace
            $table->text('access_token')->nullable();    // Enkripsi disarankan
            $table->text('refresh_token')->nullable();   // Enkripsi disarankan
            $table->timestamp('token_expires_at')->nullable();
            $table->string('status', 50)->default('connected'); // connected, disconnected, expired
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
