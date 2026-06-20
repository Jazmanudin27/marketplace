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
        Schema::table('tenants', function (Blueprint $table) {
            $table->decimal('office_latitude', 10, 8)->nullable();
            $table->decimal('office_longitude', 11, 8)->nullable();
            $table->integer('office_radius')->default(20); // default 20 meters
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->decimal('latitude_in', 10, 8)->nullable();
            $table->decimal('longitude_in', 11, 8)->nullable();
            $table->decimal('latitude_out', 10, 8)->nullable();
            $table->decimal('longitude_out', 11, 8)->nullable();
            $table->string('photo_in')->nullable();
            $table->string('photo_out')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['office_latitude', 'office_longitude', 'office_radius']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'latitude_in', 'longitude_in',
                'latitude_out', 'longitude_out',
                'photo_in', 'photo_out'
            ]);
        });
    }
};
