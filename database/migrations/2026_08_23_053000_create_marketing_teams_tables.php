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
        Schema::create('marketing_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->integer('target_qty')->default(0)->comment('Target Qty / Jumlah Pesanan');
            $table->decimal('reward_per_qty', 15, 2)->default(0)->comment('Rupiah Komisi/Bonus per Qty');
            $table->decimal('target_omset', 15, 2)->default(0)->comment('Target Omset Rupiah');
            $table->unsignedTinyInteger('period_month')->nullable()->comment('Bulan Target (1-12)');
            $table->unsignedSmallInteger('period_year')->nullable()->comment('Tahun Target (e.g. 2026)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('marketing_team_stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_team_id')->constrained('marketing_teams')->onDelete('cascade');
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['marketing_team_id', 'store_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_team_stores');
        Schema::dropIfExists('marketing_teams');
    }
};
