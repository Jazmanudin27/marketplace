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
        Schema::create('category_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->string('marketplace_category_id', 100);
            $table->string('marketplace_category_name', 255)->nullable();
            $table->timestamps();

            $table->unique(['category_id', 'store_id'], 'cat_store_unique');
        });

        Schema::create('brand_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('brand_id')->constrained()->onDelete('cascade');
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->string('marketplace_brand_id', 100);
            $table->string('marketplace_brand_name', 255)->nullable();
            $table->timestamps();

            $table->unique(['brand_id', 'store_id'], 'brand_store_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brand_mappings');
        Schema::dropIfExists('category_mappings');
    }
};
