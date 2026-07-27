<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tailors', function (Blueprint $table) {
            if (!Schema::hasColumn('tailors', 'category')) {
                $table->string('category')->default('Penjahit')->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tailors', function (Blueprint $table) {
            if (Schema::hasColumn('tailors', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
