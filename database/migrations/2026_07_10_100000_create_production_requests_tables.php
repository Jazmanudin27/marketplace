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
        Schema::create('production_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('request_number', 100)->unique();
            $table->string('request_type', 50); // stock, po
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->foreignId('store_id')->nullable()->constrained()->onDelete('set null');
            $table->string('customer_name')->nullable();
            $table->string('customer_phone', 50)->nullable();
            $table->text('shipping_address')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('status', 50)->default('pending'); // pending, approved, rejected, completed
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('production_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('master_product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('price', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_request_items');
        Schema::dropIfExists('production_requests');
    }
};
