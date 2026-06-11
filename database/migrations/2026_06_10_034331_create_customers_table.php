<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('marketplace_username')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('tags')->nullable(); // Reseller, VIP, dll
            $table->timestamps();
            
            // Unique constraint on tenant_id + marketplace_username
            $table->unique(['tenant_id', 'marketplace_username']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
