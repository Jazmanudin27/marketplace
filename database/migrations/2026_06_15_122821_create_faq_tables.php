<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faq_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name', 100);
            $table->string('subtitle', 255)->nullable();
            $table->string('icon', 50)->default('fas fa-question-circle');
            $table->string('color', 20)->default('#6C63FF');
            $table->string('color_rgb', 50)->default('108, 99, 255');
            $table->string('read_time', 20)->nullable();
            $table->string('workflow_title', 100)->default('Alur Kerja');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('faq_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faq_category_id')->constrained('faq_categories')->onDelete('cascade');
            $table->string('type', 20); // 'workflow' or 'faq'
            $table->text('title');
            $table->text('content');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faq_items');
        Schema::dropIfExists('faq_categories');
    }
};
