<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('late_penalty_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->integer('min_minutes');
            $table->decimal('penalty_amount', 15, 2);
            $table->timestamps();

            $table->unique(['tenant_id', 'min_minutes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('late_penalty_rules');
    }
};
