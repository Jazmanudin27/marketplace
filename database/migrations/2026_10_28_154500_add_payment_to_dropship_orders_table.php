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
        if (Schema::hasTable('dropship_orders')) {
            Schema::table('dropship_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('dropship_orders', 'paid_amount')) {
                    $table->decimal('paid_amount', 15, 2)->default(0)->after('total_amount');
                }
                if (!Schema::hasColumn('dropship_orders', 'payment_status')) {
                    $table->string('payment_status', 30)->default('unpaid')->after('paid_amount')->comment('unpaid, partial, paid');
                }
                if (!Schema::hasColumn('dropship_orders', 'payment_method')) {
                    $table->string('payment_method', 50)->nullable()->after('payment_status');
                }
            });
        }

        if (!Schema::hasTable('dropship_order_payments')) {
            Schema::create('dropship_order_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('dropship_order_id')->constrained('dropship_orders')->cascadeOnDelete();
                $table->string('payment_number', 50);
                $table->date('payment_date');
                $table->decimal('amount', 15, 2)->default(0);
                $table->string('payment_method', 50)->default('tunai');
                $table->string('payment_destination', 100)->nullable();
                $table->string('reference_number', 100)->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['tenant_id', 'dropship_order_id']);
                $table->index('payment_number');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dropship_order_payments');

        if (Schema::hasTable('dropship_orders')) {
            Schema::table('dropship_orders', function (Blueprint $table) {
                if (Schema::hasColumn('dropship_orders', 'payment_method')) {
                    $table->dropColumn('payment_method');
                }
                if (Schema::hasColumn('dropship_orders', 'payment_status')) {
                    $table->dropColumn('payment_status');
                }
                if (Schema::hasColumn('dropship_orders', 'paid_amount')) {
                    $table->dropColumn('paid_amount');
                }
            });
        }
    }
};
