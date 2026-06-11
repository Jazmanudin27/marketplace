<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('chat_conversation_id')->constrained('chat_conversations')->onDelete('cascade');
            $table->string('platform_message_id', 191)->nullable();
            $table->string('direction', 20); // inbound, outbound
            $table->string('sender_role', 20); // buyer, seller, system
            $table->string('message_type', 50)->default('text'); // text, image, file, system
            $table->text('body')->nullable();
            $table->string('media_url', 255)->nullable();
            $table->string('delivery_status', 50)->default('sent'); // received, sent, delivered, read, failed
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'chat_conversation_id']);
            $table->index(['tenant_id', 'sent_at']);
            $table->index(['platform_message_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
