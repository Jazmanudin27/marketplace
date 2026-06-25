@extends('layouts.app')
@section('title', 'Chat — ' . ($chatConversation->buyer_name ?? 'Buyer'))
@section('page-title', 'Inbox Chat')

@section('content')
<div class="row g-0 border rounded shadow-sm bg-white" style="height: calc(100vh - 160px); overflow: hidden;">
    <!-- LEFT: Conversation List Sidebar -->
    <div class="col-md-4 d-flex flex-column border-end bg-light" style="max-height: 100%;">
        <!-- Header & Search -->
        <div class="p-3 border-bottom bg-white">
            <h6 class="mb-2 fw-bold d-flex justify-content-between align-items-center">
                <span class="text-primary"><i class="fas fa-comments me-2"></i> Chat</span>
                <a href="{{ route('chats.index') }}" class="text-muted small text-decoration-none fw-semibold">
                    <i class="fas fa-th-list me-1"></i> Semua
                </a>
            </h6>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0 text-muted">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" id="sidebar-search" placeholder="Cari buyer..." autocomplete="off" class="form-control border-start-0 bg-light">
            </div>
        </div>

        <!-- Conversation list -->
        <div class="list-group list-group-flush flex-grow-1 overflow-auto bg-white" id="sidebar-conv-list">
            @foreach($conversations as $conv)
                @php
                    $ch = $conv->store->channel->code ?? '';
                    $initial = strtoupper(substr($conv->buyer_name ?? 'B', 0, 1));
                    $isActive = $conv->id === $chatConversation->id;
                    $bgGrad = $ch === 'shopee' ? 'bg-danger' : ($ch === 'tiktok' ? 'bg-dark' : 'bg-primary');
                @endphp
                <a href="{{ route('chats.show', $conv) }}"
                   class="list-group-item list-group-item-action d-flex align-items-center gap-3 border-0 border-bottom p-3 {{ $isActive ? 'active bg-primary bg-opacity-10' : '' }}"
                   data-name="{{ strtolower($conv->buyer_name ?? '') }}">
                    <!-- Avatar -->
                    <div class="position-relative rounded-circle d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0 {{ $bgGrad }}" style="width: 40px; height: 40px; font-size: 0.9rem;">
                        {{ $initial }}
                        <span class="position-absolute bottom-0 end-0 badge rounded-circle p-1 {{ $ch === 'shopee' ? 'bg-warning text-dark' : 'bg-secondary text-white' }}" style="font-size: 0.55rem; width: 16px; height: 16px; transform: translate(20%, 20%);">
                            {{ $ch === 'shopee' ? 'S' : ($ch === 'tiktok' ? 'T' : '?') }}
                        </span>
                    </div>
                    <!-- Info -->
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-bold text-dark small text-truncate mb-0.5">{{ $conv->buyer_name ?? 'Unknown Buyer' }}</div>
                        <div class="text-muted small text-truncate" style="font-size: 0.75rem;">
                            {{ \Illuminate\Support\Str::limit($conv->last_message_preview ?? '—', 38) }}
                        </div>
                    </div>
                    <!-- Time -->
                    <div class="text-muted small flex-shrink-0" style="font-size: 0.68rem;">
                        {{ optional($conv->last_message_at)->diffForHumans(['short'=>true]) ?? '—' }}
                    </div>
                </a>
            @endforeach
        </div>

        <!-- Sync Bar -->
        <div class="p-2 bg-light border-top d-flex gap-1 align-items-center flex-wrap">
            <span class="text-muted small me-1"><i class="fas fa-sync-alt me-1"></i>Sync:</span>
            @foreach($stores as $store)
                <button type="button" class="btn btn-outline-secondary btn-sm py-0.5 px-2.5 rounded-pill" style="font-size: 0.72rem;" onclick="triggerSync({{ $store->id }})">
                    {{ \Illuminate\Support\Str::limit($store->store_name, 14) }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- RIGHT: Active Chat Panel -->
    <div class="col-md-8 d-flex flex-column bg-white h-100" style="max-height: 100%;">
        <!-- Header -->
        @php
            $ch = $chatConversation->store->channel->code ?? '';
            $buyerInitial = strtoupper(substr($chatConversation->buyer_name ?? 'B', 0, 1));
            $bgGrad = $ch === 'shopee' ? 'bg-danger' : ($ch === 'tiktok' ? 'bg-dark' : 'bg-primary');
        @endphp
        <div class="p-3 border-bottom bg-light d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0 {{ $bgGrad }}" style="width: 42px; height: 42px; font-size: 1rem;">
                    {{ $buyerInitial }}
                </div>
                <div>
                    <h6 class="fw-bold text-dark mb-1">{{ $chatConversation->buyer_name ?? 'Unknown Buyer' }}</h6>
                    <div class="d-flex gap-2 align-items-center flex-wrap" style="font-size: 0.78rem;">
                        <span class="badge {{ $ch === 'shopee' ? 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25' : 'bg-dark text-white' }}">
                            @if($ch === 'shopee') 🛒 Shopee
                            @elseif($ch === 'tiktok') 🎵 TikTok Shop
                            @else {{ ucfirst($ch) }} @endif
                        </span>
                        <span class="text-muted">{{ $chatConversation->store->store_name }}</span>
                        <span class="badge {{ $chatConversation->status === 'open' ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary' }}">{{ ucfirst($chatConversation->status) }}</span>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-1">
                <button type="button" onclick="triggerSync({{ $chatConversation->store_id }})" class="btn btn-outline-secondary btn-sm" title="Refresh chat">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <a href="{{ route('chats.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i>
                </a>
            </div>
        </div>

        <!-- Message Thread -->
        <div class="flex-grow-1 overflow-auto p-3 bg-light" id="chat-thread" style="min-height: 0;">
            @php $prevDate = null; @endphp
            @forelse($chatConversation->messages as $msg)
                @php
                    $msgDate = optional($msg->sent_at)->format('d M Y');
                    $dir = $msg->direction ?? 'inbound';
                    $isOut = $dir === 'outbound';
                @endphp

                {{-- Date separator --}}
                @if($msgDate && $msgDate !== $prevDate)
                    <div class="d-flex align-items-center gap-3 my-3 text-muted" style="font-size: 0.75rem;">
                        <div class="flex-grow-1 border-top"></div>
                        <span>{{ $msgDate === now()->format('d M Y') ? 'Hari ini' : $msgDate }}</span>
                        <div class="flex-grow-1 border-top"></div>
                    </div>
                    @php $prevDate = $msgDate; @endphp
                @endif

                <div class="d-flex gap-2 mb-3 align-items-end {{ $isOut ? 'flex-row-reverse text-end' : '' }}" id="msg-{{ $msg->id }}">
                    @if(!$isOut)
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 28px; height: 28px; font-size: 0.7rem;">
                            {{ $buyerInitial }}
                        </div>
                    @endif

                    <div style="max-width: 70%;" class="{{ $isOut ? 'text-end' : 'text-start' }}">
                        {{-- Media --}}
                        @if($msg->media_url && in_array($msg->message_type, ['image']))
                            <img src="{{ $msg->media_url }}" class="img-fluid rounded border mb-1" style="max-width: 240px; cursor: pointer;" onclick="window.open(this.src,'_blank')">
                        @endif

                        <div class="p-2 rounded-3 d-inline-block text-start {{ $isOut ? 'bg-primary text-white' : 'bg-white text-dark border' }}" style="font-size: 0.875rem; line-height: 1.5; word-break: break-word;">
                            @if($msg->body)
                                {!! nl2br(e($msg->body)) !!}
                            @elseif($msg->message_type !== 'text')
                                <span class="fst-italic opacity-75">[{{ ucfirst($msg->message_type) }}]</span>
                            @else
                                <span class="fst-italic opacity-50">[pesan kosong]</span>
                            @endif
                        </div>

                        <div class="mt-1 text-muted d-flex gap-1 align-items-center justify-content-start {{ $isOut ? 'justify-content-end' : '' }}" style="font-size: 0.68rem;">
                            <span>{{ optional($msg->sent_at)->format('H:i') ?? '—' }}</span>
                            @if($isOut)
                                <i class="fas fa-check-double text-success"></i>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-5">
                    <i class="fas fa-comment-slash d-block mb-2 opacity-50" style="font-size: 2rem;"></i>
                    Belum ada pesan tersimpan untuk percakapan ini.
                </div>
            @endforelse
        </div>

        <!-- Reply Bar -->
        <div class="p-3 border-top bg-light d-flex gap-2 align-items-end">
            <textarea id="reply-textarea" class="form-control form-control-sm" placeholder="Tulis pesan... (Enter = baris baru, Ctrl+Enter = kirim)" rows="1" style="resize: none; max-height: 120px;"></textarea>
            <button type="button" class="btn btn-primary d-flex align-items-center justify-content-center flex-shrink-0" id="btn-send" title="Kirim pesan" style="width: 38px; height: 38px; border-radius: 50%;">
                <i class="fas fa-paper-plane" id="send-icon"></i>
            </button>
        </div>
    </div>
</div>

{{-- Toast notification --}}
<div id="chat-toast" class="toast position-fixed bottom-0 end-0 m-3 align-items-center text-white bg-dark border-0" role="alert" aria-live="assertive" aria-atomic="true" style="z-index: 9999; display: none;">
    <div class="d-flex">
        <div class="toast-body d-flex align-items-center gap-2">
            <i class="fas fa-info-circle text-info"></i>
            <span id="toast-msg">–</span>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ── Scroll to bottom on load
document.addEventListener('DOMContentLoaded', function () {
    scrollToBottom();
    autoResizeTextarea();
});

function scrollToBottom(smooth = false) {
    const thread = document.getElementById('chat-thread');
    if (!thread) return;
    thread.scrollTo({ top: thread.scrollHeight, behavior: smooth ? 'smooth' : 'auto' });
}

// ── Auto-resize textarea
function autoResizeTextarea() {
    const ta = document.getElementById('reply-textarea');
    if (!ta) return;
    ta.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });

    // Ctrl+Enter to send, Enter = newline
    ta.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
            e.preventDefault();
            sendMessage();
        }
    });
}

// ── Send message
document.getElementById('btn-send').addEventListener('click', sendMessage);

async function sendMessage() {
    const ta   = document.getElementById('reply-textarea');
    const btn  = document.getElementById('btn-send');
    const icon = document.getElementById('send-icon');
    const text = ta.value.trim();

    if (!text) return;

    // Disable UI
    btn.disabled = true;
    icon.className = 'fas fa-circle-notch spin';

    // Optimistic bubble
    const now = new Date();
    const timeStr = now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0');
    const tempId = 'msg-temp-' + Date.now();

    const thread = document.getElementById('chat-thread');
    const row = document.createElement('div');
    row.className = 'd-flex gap-2 mb-3 align-items-end flex-row-reverse text-end msg-sending';
    row.id = tempId;
    row.innerHTML = `
        <div style="max-width: 70%;" class="text-end">
            <div class="p-2 rounded-3 d-inline-block text-start bg-primary text-white" style="font-size: 0.875rem; line-height: 1.5; word-break: break-word;">
                ${escapeHtml(text).replace(/\n/g, '<br>')}
            </div>
            <div class="mt-1 text-muted d-flex gap-1 align-items-center justify-content-end" style="font-size: 0.68rem;">
                <span>${timeStr}</span>
                <i class="fas fa-clock" style="opacity:.5;font-size:.65rem;"></i>
            </div>
        </div>`;
    thread.appendChild(row);
    scrollToBottom(true);

    ta.value = '';
    ta.style.height = 'auto';

    try {
        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('message', text);

        const res = await fetch('{{ route("chats.reply", $chatConversation) }}', {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: formData,
        });

        const data = await res.json();

        if (data.success) {
            // Mark as sent
            const tempEl = document.getElementById(tempId);
            if (tempEl) {
                tempEl.classList.remove('msg-sending');
                tempEl.querySelector('.mt-1').innerHTML =
                    `<span>${timeStr}</span><i class="fas fa-check-double text-success"></i>`;
            }
            showToast('✅ Pesan terkirim!', 2500);
        } else {
            removeOptimistic(tempId);
            showToast('❌ ' + (data.message || 'Gagal mengirim'), 4000);
            ta.value = text; // Restore text
        }
    } catch (err) {
        removeOptimistic(tempId);
        showToast('❌ Koneksi error, coba lagi.', 4000);
        ta.value = text;
    } finally {
        btn.disabled = false;
        icon.className = 'fas fa-paper-plane';
    }
}

function removeOptimistic(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
}

function escapeHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
              .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

// ── Sidebar search
document.getElementById('sidebar-search').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#sidebar-conv-list .list-group-item').forEach(item => {
        const name = item.dataset.name || '';
        item.style.setProperty('display', name.includes(q) ? 'flex' : 'none', name.includes(q) ? '' : 'important');
    });
});

// ── Sync trigger
function triggerSync(storeId) {
    showToast('🔄 Sync sedang berjalan…', 3000);
    const body = new FormData();
    body.append('_token', '{{ csrf_token() }}');
    if (storeId) body.append('store_id', storeId);

    fetch('{{ route("chats.sync") }}', {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body,
    })
    .then(async r => {
        const d = await r.json();
        if (r.ok && d.success) {
            showToast('✅ Sync berhasil!', 1500);
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast('❌ ' + (d.message || 'Gagal sinkronisasi'), 8000);
        }
    })
    .catch(() => {
        showToast('❌ Koneksi error atau gagal sinkronisasi.', 5000);
    });
}

// ── Toast
function showToast(msg, duration = 3000) {
    const toast = document.getElementById('chat-toast');
    document.getElementById('toast-msg').textContent = msg;
    toast.style.display = 'block';
    setTimeout(() => { toast.style.display = 'none'; }, duration);
}
</script>
@endpush
