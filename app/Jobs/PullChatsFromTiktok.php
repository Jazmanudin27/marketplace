<?php

namespace App\Jobs;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Store;
use App\Services\TiktokService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PullChatsFromTiktok implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries   = 1; // Tidak perlu retry — fitur belum diaktifkan

    public function __construct(public readonly ?int $storeId = null)
    {
    }

    public function handle(TiktokService $tiktok): void
    {
        $query = Store::with('channel')
            ->whereHas('channel', fn($q) => $q->where('code', 'tiktok'))
            ->where('status', 'connected')
            ->whereNotNull('access_token');

        if ($this->storeId) {
            $query->where('id', $this->storeId);
        }

        $stores = $query->get();
        $errors = [];

        foreach ($stores as $store) {
            try {
                $this->pullForStore($tiktok, $store);
            } catch (\Throwable $e) {
                $errors[] = "[Toko {$store->store_name}]: " . $e->getMessage();
            }
        }

        if (!empty($errors)) {
            // Hanya log, tidak throw — fitur chat belum di-approve marketplace
            Log::warning('[PullChatsFromTiktok] Beberapa toko gagal pull chat', ['errors' => $errors]);
        }
    }

    private function pullForStore(TiktokService $tiktok, Store $store): void
    {
        $accessToken = $store->access_token;
        $shopCipher = $store->shop_cipher ?? '';

        if (!$accessToken || !$shopCipher) {
            Log::warning('[PullChatsFromTiktok] Toko tidak memiliki access_token atau shop_cipher', ['store_id' => $store->id]);
            return;
        }

        $response = $tiktok->getChatConversationList($accessToken, $shopCipher, 20);
        $conversations = $response['conversations'] ?? [];

        foreach ($conversations as $conv) {
            $conversationId = (string) ($conv['id'] ?? '');
            if (!$conversationId)
                continue;

            $buyerName = $conv['participant_list'][0]['name'] ?? 'Buyer TikTok';
            $buyerAvatar = $conv['participant_list'][0]['avatar'] ?? null;

            $dbConv = ChatConversation::updateOrCreate(
                ['store_id' => $store->id, 'platform_conversation_id' => $conversationId],
                [
                    'tenant_id' => $store->tenant_id,
                    'buyer_name' => $buyerName,
                    'buyer_avatar_url' => $buyerAvatar,
                    'status' => 'open',
                    'last_message_at' => isset($conv['latest_message_time'])
                        ? \Carbon\Carbon::createFromTimestamp($conv['latest_message_time'])
                        : null,
                    'last_message_preview' => $conv['latest_message']['content'] ?? null,
                    'metadata' => $conv,
                ]
            );

            $this->pullMessages($tiktok, $store, $dbConv, $accessToken, $shopCipher);
        }

        Log::info('[PullChatsFromTiktok] Selesai pull chat', [
            'store_id' => $store->id,
            'total' => count($conversations),
        ]);
    }

    private function pullMessages(TiktokService $tiktok, Store $store, ChatConversation $conv, string $accessToken, string $shopCipher): void
    {
        try {
            $response = $tiktok->getChatMessages($accessToken, $shopCipher, $conv->platform_conversation_id, 50);
            $messages = $response['messages'] ?? [];

            foreach ($messages as $msg) {
                $platformMsgId = (string) ($msg['id'] ?? '');
                if (!$platformMsgId)
                    continue;

                if (ChatMessage::where('platform_message_id', $platformMsgId)->exists())
                    continue;

                $senderType = $msg['type'] ?? 'BUYER';
                $senderRole = str_contains(strtolower($senderType), 'seller') ? 'seller' : 'buyer';
                $direction = $senderRole === 'seller' ? 'outbound' : 'inbound';
                $msgType = strtolower($msg['message_type'] ?? 'TEXT');
                $body = null;

                if ($msgType === 'text' || $msgType === 'TEXT') {
                    $body = $msg['content']['text'] ?? null;
                }

                $sentAt = isset($msg['create_time'])
                    ? \Carbon\Carbon::createFromTimestamp($msg['create_time'])
                    : now();

                ChatMessage::create([
                    'tenant_id' => $store->tenant_id,
                    'chat_conversation_id' => $conv->id,
                    'platform_message_id' => $platformMsgId,
                    'direction' => $direction,
                    'sender_role' => $senderRole,
                    'message_type' => $msgType,
                    'body' => $body,
                    'media_url' => $msg['content']['url'] ?? null,
                    'delivery_status' => 'delivered',
                    'sent_at' => $sentAt,
                    'payload' => $msg,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('[PullChatsFromTiktok] Gagal pull pesan', [
                'conversation_id' => $conv->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
