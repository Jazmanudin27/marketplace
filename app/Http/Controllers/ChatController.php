<?php

namespace App\Http\Controllers;

use App\Jobs\PullChatsFromShopee;
use App\Jobs\PullChatsFromTiktok;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Store;
use App\Services\ShopeeService;
use App\Services\TiktokService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    // -------------------------------------------------------------------------
    // Index — Daftar percakapan (tampilan dua panel / full-page list)
    // -------------------------------------------------------------------------
    public function index(Request $request)
    {
        $tenantId    = Auth::user()->tenant_id;
        $storeId     = $request->integer('store_id');
        $status      = $request->string('status')->toString();
        $channelCode = $request->string('channel')->toString();
        $search      = $request->string('search')->toString();

        $conversations = ChatConversation::with(['store.channel'])
            ->where('tenant_id', $tenantId)
            ->when($storeId,     fn ($q) => $q->where('store_id', $storeId))
            ->when($status,      fn ($q) => $q->where('status', $status))
            ->when($channelCode, function ($q) use ($channelCode) {
                $q->whereHas('store.channel', fn ($cq) => $cq->where('code', $channelCode));
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('buyer_name', 'like', "%{$search}%")
                          ->orWhere('last_message_preview', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->paginate(30)
            ->withQueryString();

        $stores = Store::with('channel')
            ->where('tenant_id', $tenantId)
            ->orderBy('store_name')
            ->get();

        return view('chats.index', compact('conversations', 'stores', 'storeId', 'status', 'channelCode', 'search'));
    }

    // -------------------------------------------------------------------------
    // Show — Detail percakapan + riwayat pesan
    // -------------------------------------------------------------------------
    public function show(ChatConversation $chatConversation)
    {
        abort_unless($chatConversation->tenant_id === Auth::user()->tenant_id, 403);

        $chatConversation->load([
            'store.channel',
            'messages' => function ($query) {
                $query->orderBy('sent_at')->orderBy('id');
            },
        ]);

        // Sidebar: percakapan terbaru (untuk two-panel layout)
        $conversations = ChatConversation::with('store.channel')
            ->where('tenant_id', Auth::user()->tenant_id)
            ->orderByDesc('last_message_at')
            ->limit(30)
            ->get();

        $stores = Store::with('channel')
            ->where('tenant_id', Auth::user()->tenant_id)
            ->orderBy('store_name')
            ->get();

        return view('chats.show', compact('chatConversation', 'conversations', 'stores'));
    }

    // -------------------------------------------------------------------------
    // Reply — Kirim pesan balasan ke marketplace
    // -------------------------------------------------------------------------
    public function reply(Request $request, ChatConversation $chatConversation)
    {
        abort_unless($chatConversation->tenant_id === Auth::user()->tenant_id, 403);

        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $store       = $chatConversation->load('store.channel')->store;
        $channel     = $store->channel->code ?? '';
        $messageText = trim($request->input('message'));

        try {
            if ($channel === 'shopee') {
                $this->replyShopee($store, $chatConversation, $messageText);
            } elseif ($channel === 'tiktok') {
                $this->replyTiktok($store, $chatConversation, $messageText);
            } else {
                return back()->with('error', 'Channel marketplace tidak didukung untuk reply: ' . $channel);
            }

            // Simpan pesan ke database
            ChatMessage::create([
                'tenant_id'            => Auth::user()->tenant_id,
                'chat_conversation_id' => $chatConversation->id,
                'direction'            => 'outbound',
                'sender_role'          => 'seller',
                'message_type'         => 'text',
                'body'                 => $messageText,
                'delivery_status'      => 'sent',
                'sent_at'              => now(),
            ]);

            // Update preview percakapan
            $chatConversation->update([
                'last_message_at'      => now(),
                'last_message_preview' => $messageText,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pesan berhasil dikirim',
                    'data'    => [
                        'body'    => $messageText,
                        'sent_at' => now()->format('d/m/Y H:i'),
                    ],
                ]);
            }

            return back()->with('success', 'Pesan berhasil dikirim ke ' . ucfirst($channel) . '!');

        } catch (\Throwable $e) {
            Log::error('[ChatController] Gagal mengirim pesan', [
                'conversation_id' => $chatConversation->id,
                'channel'         => $channel,
                'error'           => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', 'Gagal mengirim pesan: ' . $e->getMessage());
        }
    }

    // Sync — Trigger pull chat manual per toko atau semua (secara sinkron agar langsung mengembalikan feedback error)
    // -------------------------------------------------------------------------
    public function sync(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $storeId  = $request->integer('store_id') ?: null;

        try {
            if ($storeId) {
                $store = Store::where('id', $storeId)
                    ->where('tenant_id', $tenantId)
                    ->with('channel')
                    ->firstOrFail();

                $channel = $store->channel->code ?? '';

                if ($channel === 'shopee') {
                    PullChatsFromShopee::dispatchSync($storeId);
                } elseif ($channel === 'tiktok') {
                    PullChatsFromTiktok::dispatchSync($storeId);
                }
            } else {
                // Jalankan sync untuk semua toko secara berurutan
                if (Auth::user()->isSuperAdmin() && $tenantId == 1) {
                    PullChatsFromShopee::dispatchSync();
                    PullChatsFromTiktok::dispatchSync();
                } else {
                    $stores = Store::where('tenant_id', $tenantId)->with('channel')->get();
                    foreach ($stores as $store) {
                        if ($store->channel->code === 'shopee') {
                            PullChatsFromShopee::dispatchSync($store->id);
                        } elseif ($store->channel->code === 'tiktok') {
                            PullChatsFromTiktok::dispatchSync($store->id);
                        }
                    }
                }
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sinkronisasi chat berhasil diselesaikan.'
                ]);
            }

            return back()->with('success', 'Sinkronisasi chat berhasil diselesaikan.');

        } catch (\Throwable $e) {
            Log::error('[ChatController] Gagal sinkronisasi chat manual', [
                'store_id' => $storeId,
                'error'    => $e->getMessage()
            ]);

            $friendlyMessage = $this->getFriendlyErrorMessage($e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $friendlyMessage
                ], 422);
            }

            return back()->with('error', $friendlyMessage);
        }
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function getFriendlyErrorMessage(string $errorMsg): string
    {
        if (str_contains($errorMsg, 'error_api_permission') || str_contains($errorMsg, 'no permission to this API')) {
            return 'Aplikasi Shopee Anda tidak memiliki izin untuk Seller Chat API. Harap ajukan/aktifkan scope "Customer Service" (Seller Chat) di Shopee Open Platform Console.';
        }
        if (str_contains($errorMsg, 'access scopes') || str_contains($errorMsg, 'not authorized to access')) {
            return 'Aplikasi TikTok Anda tidak memiliki izin untuk Chat CS API. Silakan ajukan scope "Customer Service" di TikTok Developer Console dan lakukan otorisasi ulang toko Anda.';
        }
        if (str_contains($errorMsg, 'invalid_acceess_token') || str_contains($errorMsg, 'Invalid access_token')) {
            return 'Token toko tidak valid atau telah kedaluwarsa. Silakan hubungkan kembali toko Anda di pengaturan.';
        }
        return 'Gagal sinkronisasi: ' . $errorMsg;
    }

    private function replyShopee(Store $store, ChatConversation $conv, string $text): void
    {
        $shopee = app(ShopeeService::class);

        // Refresh token jika expired
        if ($this->isTokenExpired($store)) {
            $this->refreshShopeeToken($store);
            $store->refresh();
        }

        $shopee->sendChatMessage(
            $store->access_token,
            (int) $store->marketplace_store_id,
            $conv->platform_conversation_id,
            $text
        );
    }

    private function replyTiktok(Store $store, ChatConversation $conv, string $text): void
    {
        $tiktok = app(TiktokService::class);
        $tiktok->sendChatMessage(
            $store->access_token,
            $store->shop_cipher ?? '',
            $conv->platform_conversation_id,
            $text
        );
    }

    private function isTokenExpired(Store $store): bool
    {
        if (!$store->token_expires_at) return false;
        return now()->gte($store->token_expires_at);
    }

    private function refreshShopeeToken(Store $store): void
    {
        try {
            $shopee = app(ShopeeService::class);
            $data   = $shopee->refreshAccessToken($store->refresh_token, (int) $store->marketplace_store_id);

            $store->update([
                'access_token'    => $data['access_token'],
                'refresh_token'   => $data['refresh_token'],
                'token_expires_at' => now()->addSeconds($data['expire_in'] ?? 3600),
            ]);
        } catch (\Throwable $e) {
            Log::warning('[ChatController] Gagal refresh Shopee token', ['error' => $e->getMessage()]);
        }
    }
}
