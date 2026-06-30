<?php

namespace App\Jobs;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Store;
use App\Services\ShopeeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PullChatsFromShopee implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries   = 1; // Tidak perlu retry — fitur belum diaktifkan

    public function __construct(public readonly ?int $storeId = null)
    {
    }

    public function handle(ShopeeService $shopee): void
    {
        $query = Store::with('channel')
            ->whereHas('channel', fn ($q) => $q->where('code', 'shopee'))
            ->where('status', '!=', 'disconnected')
            ->whereNotNull('access_token');

        if ($this->storeId) {
            $query->where('id', $this->storeId);
        }

        $stores = $query->get();
        $errors = [];

        foreach ($stores as $store) {
            try {
                $this->pullForStore($shopee, $store);
            } catch (\Throwable $e) {
                $errors[] = "[Toko {$store->store_name}]: " . $e->getMessage();
            }
        }

        if (!empty($errors)) {
            // Hanya log, tidak throw — fitur chat belum di-approve marketplace
            Log::warning('[PullChatsFromShopee] Beberapa toko gagal pull chat', ['errors' => $errors]);
        }
    }

    private function pullForStore(ShopeeService $shopee, Store $store): void
    {
        $accessToken = $store->getValidAccessToken();
        $shopId      = (int) $store->marketplace_store_id;

        if (!$accessToken || !$shopId) {
            Log::warning('[PullChatsFromShopee] Toko tidak memiliki access_token atau shop_id', ['store_id' => $store->id]);
            return;
        }

        $response = $shopee->getChatConversationList($accessToken, $shopId, 25);
        $conversations = $response['conversations'] ?? [];

        foreach ($conversations as $conv) {
            $conversationId = (string) ($conv['conversation_id'] ?? '');
            if (!$conversationId) continue;

            // Upsert conversation
            $dbConv = ChatConversation::updateOrCreate(
                ['store_id' => $store->id, 'platform_conversation_id' => $conversationId],
                [
                    'tenant_id'            => $store->tenant_id,
                    'buyer_name'           => $conv['to_name'] ?? $conv['buyer_username'] ?? 'Buyer',
                    'buyer_avatar_url'     => $conv['to_avatar'] ?? null,
                    'status'               => 'open',
                    'last_message_at'      => isset($conv['last_message_timestamp'])
                        ? \Carbon\Carbon::createFromTimestamp($conv['last_message_timestamp'])
                        : null,
                    'last_message_preview' => $conv['last_message_content']['text'] ?? null,
                    'metadata'             => $conv,
                ]
            );

            // Pull pesan dalam percakapan ini
            $this->pullMessages($shopee, $store, $dbConv, $accessToken, $shopId);
        }

        Log::info('[PullChatsFromShopee] Selesai pull chat', [
            'store_id' => $store->id,
            'total'    => count($conversations),
        ]);
    }

    private function pullMessages(ShopeeService $shopee, Store $store, ChatConversation $conv, string $accessToken, int $shopId): void
    {
        try {
            $response = $shopee->getChatMessages($accessToken, $shopId, $conv->platform_conversation_id, 25);
            $messages = $response['messages'] ?? [];

            foreach ($messages as $msg) {
                $platformMsgId = (string) ($msg['message_id'] ?? '');
                if (!$platformMsgId) continue;

                // Skip jika sudah ada
                if (ChatMessage::where('platform_message_id', $platformMsgId)->exists()) continue;

                $senderRole = ($msg['from_id'] ?? '') === 'seller' ? 'seller' : 'buyer';
                $direction  = $senderRole === 'seller' ? 'outbound' : 'inbound';
                $body       = $msg['content']['text'] ?? null;
                $messageType = $msg['message_type'] ?? 'text';
                $sentAt     = isset($msg['created_timestamp'])
                    ? \Carbon\Carbon::createFromTimestamp($msg['created_timestamp'])
                    : now();

                ChatMessage::create([
                    'tenant_id'              => $store->tenant_id,
                    'chat_conversation_id'   => $conv->id,
                    'platform_message_id'    => $platformMsgId,
                    'direction'              => $direction,
                    'sender_role'            => $senderRole,
                    'message_type'           => strtolower($messageType),
                    'body'                   => $body,
                    'media_url'              => $msg['content']['image_url'] ?? $msg['content']['file_url'] ?? null,
                    'delivery_status'        => 'delivered',
                    'sent_at'                => $sentAt,
                    'payload'                => $msg,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('[PullChatsFromShopee] Gagal pull pesan', [
                'conversation_id' => $conv->id,
                'error'           => $e->getMessage(),
            ]);
        }
    }
}
