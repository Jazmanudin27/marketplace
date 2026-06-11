@extends('layouts.app')
@section('title', 'Chat — ' . ($chatConversation->buyer_name ?? 'Buyer'))
@section('page-title', 'Inbox Chat')

@push('styles')
<style>
/* ============================================================
   CHAT SHOW — TWO-PANEL + BUBBLE MESSAGES + REPLY FORM
   ============================================================ */
.chat-layout {
    display: flex;
    height: calc(100vh - 120px);
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 32px rgba(0,0,0,0.25);
}

/* ── LEFT: Conv List ── */
.chat-sidebar {
    width: 320px;
    min-width: 280px;
    display: flex;
    flex-direction: column;
    border-right: 1px solid var(--border);
    background: rgba(255,255,255,0.02);
}

.chat-sidebar-header {
    padding: 1rem 1.1rem .65rem;
    border-bottom: 1px solid var(--border);
}

.chat-sidebar-header h2 {
    font-size: .95rem;
    font-weight: 700;
    margin: 0 0 .6rem;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.chat-search-bar {
    position: relative;
}

.chat-search-bar input {
    width: 100%;
    background: rgba(255,255,255,0.06);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: .42rem .9rem .42rem 2.3rem;
    color: var(--text-primary);
    font-size: .82rem;
    transition: all .2s;
}

.chat-search-bar input:focus { outline: none; border-color: var(--primary); }

.chat-search-bar i {
    position: absolute; left: .85rem; top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted); font-size: .78rem;
}

.chat-conv-list {
    flex: 1;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--border) transparent;
}

.conv-item {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .75rem 1.1rem;
    border-bottom: 1px solid rgba(255,255,255,0.04);
    cursor: pointer;
    text-decoration: none;
    color: var(--text-primary);
    transition: background .15s;
    position: relative;
}

.conv-item:hover { background: rgba(255,255,255,0.04); color: var(--text-primary); }
.conv-item.active { background: rgba(99,102,241,.14); }
.conv-item.active::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 3px;
    background: var(--primary);
    border-radius: 0 2px 2px 0;
}

.conv-avatar {
    width: 40px; height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: .9rem;
    color: #fff;
    flex-shrink: 0;
    position: relative;
}

.channel-dot {
    position: absolute; bottom: -2px; right: -2px;
    width: 16px; height: 16px;
    border-radius: 50%;
    border: 2px solid var(--bg-card);
    font-size: .5rem;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; color: #fff;
}

.channel-dot.shopee { background: #ee4d2d; }
.channel-dot.tiktok  { background: #010101; }

.conv-info { flex: 1; min-width: 0; }
.conv-name {
    font-size: .85rem; font-weight: 600;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.conv-preview {
    font-size: .75rem; color: var(--text-muted);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

.conv-time { font-size: .68rem; color: var(--text-muted); flex-shrink: 0; }

.sync-bar {
    padding: .5rem 1.1rem;
    border-top: 1px solid var(--border);
    display: flex; gap: .4rem; flex-wrap: wrap; align-items: center;
    background: rgba(0,0,0,.15);
}

.sync-bar-label { font-size: .72rem; color: var(--text-muted); }

.btn-sync-sm {
    padding: .2rem .6rem;
    border-radius: 20px; font-size: .72rem; font-weight: 500;
    border: 1px solid var(--border); background: transparent;
    color: var(--text-muted); cursor: pointer; transition: all .2s;
}
.btn-sync-sm:hover { border-color: var(--primary); color: var(--primary); }

/* ── RIGHT: Chat Panel ── */
.chat-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
}

/* Chat header */
.chat-panel-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: .9rem 1.25rem;
    border-bottom: 1px solid var(--border);
    background: rgba(0,0,0,.1);
}

.chat-panel-avatar {
    width: 42px; height: 42px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; color: #fff; font-size: 1rem; flex-shrink: 0;
}

.chat-panel-info { flex: 1; min-width: 0; }
.chat-panel-name { font-size: 1rem; font-weight: 700; }
.chat-panel-sub  { font-size: .78rem; color: var(--text-muted); display: flex; gap: .5rem; align-items: center; flex-wrap: wrap; }

.channel-tag {
    display: inline-flex; align-items: center; gap: .22rem;
    padding: .12rem .5rem; border-radius: 10px; font-size: .7rem; font-weight: 600;
}
.channel-shopee { background: rgba(238,77,45,.18); color: #ee4d2d; }
.channel-tiktok  { background: rgba(1,1,1,.35); color: #69c9d0; border: 1px solid rgba(105,201,208,.3); }

.status-badge { display: inline-block; padding: .1rem .45rem; border-radius: 10px; font-size: .68rem; font-weight: 600; }
.status-open     { background: rgba(34,197,94,.15); color: #22c55e; }
.status-closed   { background: rgba(100,116,139,.15); color: #94a3b8; }
.status-archived { background: rgba(71,85,105,.15); color: #64748b; }

/* Message thread */
.chat-thread {
    flex: 1;
    overflow-y: auto;
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: .65rem;
    scrollbar-width: thin;
    scrollbar-color: var(--border) transparent;
}

.msg-row {
    display: flex;
    gap: .6rem;
    align-items: flex-end;
}

.msg-row.outbound {
    flex-direction: row-reverse;
}

.msg-sender-avatar {
    width: 28px; height: 28px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    display: flex; align-items: center; justify-content: center;
    font-size: .7rem; font-weight: 700; color: #fff;
    flex-shrink: 0;
}

.msg-bubble {
    max-width: 68%;
    padding: .65rem .875rem;
    border-radius: 14px;
    font-size: .875rem;
    line-height: 1.55;
    position: relative;
    word-break: break-word;
}

.msg-row.inbound .msg-bubble {
    background: rgba(255,255,255,.07);
    border: 1px solid var(--border);
    border-bottom-left-radius: 4px;
    color: var(--text-primary);
}

.msg-row.outbound .msg-bubble {
    background: linear-gradient(135deg, rgba(99,102,241,.3), rgba(139,92,246,.3));
    border: 1px solid rgba(99,102,241,.35);
    border-bottom-right-radius: 4px;
    color: var(--text-primary);
}

.msg-meta {
    font-size: .68rem;
    color: var(--text-muted);
    margin-top: .3rem;
    display: flex;
    gap: .4rem;
    align-items: center;
}

.msg-row.outbound .msg-meta { justify-content: flex-end; }

.msg-check { color: #22c55e; }

/* Image media */
.msg-image {
    max-width: 100%; border-radius: 10px; margin-bottom: .35rem;
    cursor: pointer; transition: opacity .2s;
}
.msg-image:hover { opacity: .88; }

/* Date separator */
.date-separator {
    display: flex; align-items: center; gap: .75rem;
    margin: .5rem 0; color: var(--text-muted); font-size: .75rem;
}
.date-separator::before, .date-separator::after {
    content: ''; flex: 1; height: 1px; background: var(--border);
}

/* Optimistic message (sending state) */
.msg-sending { opacity: .55; }

/* Reply form */
.chat-reply-bar {
    border-top: 1px solid var(--border);
    padding: .875rem 1.25rem;
    background: rgba(0,0,0,.08);
    display: flex;
    gap: .75rem;
    align-items: flex-end;
}

.reply-textarea {
    flex: 1;
    background: rgba(255,255,255,.06);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: .65rem .9rem;
    color: var(--text-primary);
    font-size: .875rem;
    resize: none;
    min-height: 44px;
    max-height: 140px;
    line-height: 1.5;
    transition: border-color .2s;
    font-family: inherit;
}

.reply-textarea:focus { outline: none; border-color: var(--primary); }
.reply-textarea::placeholder { color: var(--text-muted); }

.btn-send {
    width: 44px; height: 44px;
    border-radius: 50%;
    background: var(--primary);
    border: none;
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
    cursor: pointer;
    transition: all .2s;
    flex-shrink: 0;
}
.btn-send:hover { background: #4f46e5; transform: scale(1.05); }
.btn-send:disabled { opacity: .5; cursor: not-allowed; transform: none; }

/* Spinner */
@keyframes spin { from{transform:rotate(0)} to{transform:rotate(360deg)} }
.spin { animation: spin 1s linear infinite; }

/* Responsive */
@media (max-width: 768px) {
    .chat-layout { flex-direction: column; height: auto; }
    .chat-sidebar { width: 100%; min-width: 0; border-right: none; border-bottom: 1px solid var(--border); height: 45vh; }
    .chat-main { height: 55vh; }
    .msg-bubble { max-width: 85%; }
}
</style>
@endpush

@section('content')
<div class="chat-layout">

    {{-- ────────────── LEFT: Conversation List Sidebar ────────────── --}}
    <div class="chat-sidebar">
        <div class="chat-sidebar-header">
            <h2>
                <span><i class="fas fa-comments" style="color:var(--primary)"></i> Chat</span>
                <a href="{{ route('chats.index') }}" style="font-size:.75rem; color:var(--text-muted); text-decoration:none; font-weight:500;">
                    <i class="fas fa-th-list"></i> Semua
                </a>
            </h2>
            <div class="chat-search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="sidebar-search" placeholder="Cari buyer..." autocomplete="off">
            </div>
        </div>

        <div class="chat-conv-list" id="sidebar-conv-list">
            @foreach($conversations as $conv)
                @php
                    $ch = $conv->store->channel->code ?? '';
                    $initial = strtoupper(substr($conv->buyer_name ?? 'B', 0, 1));
                    $isActive = $conv->id === $chatConversation->id;
                @endphp
                <a href="{{ route('chats.show', $conv) }}"
                   class="conv-item {{ $isActive ? 'active' : '' }}"
                   data-name="{{ strtolower($conv->buyer_name ?? '') }}">
                    <div class="conv-avatar" style="background:linear-gradient(135deg,
                        {{ $ch === 'shopee' ? '#ee4d2d,#f97316' : ($ch === 'tiktok' ? '#010101,#1d1d1d' : '#6366f1,#8b5cf6') }});">
                        {{ $initial }}
                        <span class="channel-dot {{ $ch }}">{{ $ch === 'shopee' ? 'S' : ($ch === 'tiktok' ? 'T' : '?') }}</span>
                    </div>
                    <div class="conv-info">
                        <div class="conv-name">{{ $conv->buyer_name ?? 'Unknown Buyer' }}</div>
                        <div class="conv-preview">{{ \Illuminate\Support\Str::limit($conv->last_message_preview ?? '—', 38) }}</div>
                    </div>
                    <div class="conv-time">{{ optional($conv->last_message_at)->diffForHumans(['short'=>true]) ?? '—' }}</div>
                </a>
            @endforeach
        </div>

        <div class="sync-bar">
            <span class="sync-bar-label"><i class="fas fa-sync-alt"></i></span>
            @foreach($stores as $store)
                <button type="button" class="btn-sync-sm" onclick="triggerSync({{ $store->id }})">
                    {{ \Illuminate\Support\Str::limit($store->store_name, 14) }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- ────────────── RIGHT: Chat Panel ────────────── --}}
    <div class="chat-main">

        {{-- Header --}}
        @php
            $ch = $chatConversation->store->channel->code ?? '';
            $buyerInitial = strtoupper(substr($chatConversation->buyer_name ?? 'B', 0, 1));
        @endphp
        <div class="chat-panel-header">
            <div class="chat-panel-avatar" style="background:linear-gradient(135deg,
                {{ $ch === 'shopee' ? '#ee4d2d,#f97316' : ($ch === 'tiktok' ? '#010101,#2d2d2d' : '#6366f1,#8b5cf6') }});">
                {{ $buyerInitial }}
            </div>
            <div class="chat-panel-info">
                <div class="chat-panel-name">{{ $chatConversation->buyer_name ?? 'Unknown Buyer' }}</div>
                <div class="chat-panel-sub">
                    <span class="channel-tag channel-{{ $ch }}">
                        @if($ch === 'shopee') 🛒 Shopee
                        @elseif($ch === 'tiktok') 🎵 TikTok Shop
                        @else {{ ucfirst($ch) }} @endif
                    </span>
                    <span>{{ $chatConversation->store->store_name }}</span>
                    <span class="status-badge status-{{ $chatConversation->status }}">{{ ucfirst($chatConversation->status) }}</span>
                </div>
            </div>
            <div style="display:flex; gap:.5rem;">
                <button type="button" onclick="triggerSync({{ $chatConversation->store_id }})"
                        class="btn-sync-sm" title="Refresh chat">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <a href="{{ route('chats.index') }}" class="btn-sync-sm" style="text-decoration:none; display:flex; align-items:center;">
                    <i class="fas fa-arrow-left"></i>
                </a>
            </div>
        </div>

        {{-- Message Thread --}}
        <div class="chat-thread" id="chat-thread">

            @php $prevDate = null; @endphp
            @forelse($chatConversation->messages as $msg)
                @php
                    $msgDate = optional($msg->sent_at)->format('d M Y');
                    $dir = $msg->direction ?? 'inbound';
                    $isOut = $dir === 'outbound';
                @endphp

                {{-- Date separator --}}
                @if($msgDate && $msgDate !== $prevDate)
                    <div class="date-separator">{{ $msgDate === now()->format('d M Y') ? 'Hari ini' : $msgDate }}</div>
                    @php $prevDate = $msgDate; @endphp
                @endif

                <div class="msg-row {{ $dir }}" id="msg-{{ $msg->id }}">
                    @if(!$isOut)
                        <div class="msg-sender-avatar">{{ $buyerInitial }}</div>
                    @endif

                    <div>
                        {{-- Media --}}
                        @if($msg->media_url && in_array($msg->message_type, ['image']))
                            <img src="{{ $msg->media_url }}" class="msg-image"
                                 alt="Gambar" onclick="window.open(this.src,'_blank')">
                        @endif

                        <div class="msg-bubble">
                            @if($msg->body)
                                {!! nl2br(e($msg->body)) !!}
                            @elseif($msg->message_type !== 'text')
                                <span style="opacity:.6;font-style:italic;">[{{ ucfirst($msg->message_type) }}]</span>
                            @else
                                <span style="opacity:.4;font-style:italic;">[pesan kosong]</span>
                            @endif
                        </div>

                        <div class="msg-meta">
                            <span>{{ optional($msg->sent_at)->format('H:i') ?? '—' }}</span>
                            @if($isOut)
                                <i class="fas fa-check-double msg-check"></i>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div style="text-align:center; color:var(--text-muted); padding:3rem 1rem;">
                    <i class="fas fa-comment-slash" style="font-size:2rem; display:block; margin-bottom:.75rem; opacity:.35;"></i>
                    Belum ada pesan tersimpan untuk percakapan ini.
                </div>
            @endforelse
        </div>

        {{-- Reply Bar --}}
        <div class="chat-reply-bar">
            <textarea
                id="reply-textarea"
                class="reply-textarea"
                placeholder="Tulis pesan... (Enter = baris baru, Ctrl+Enter = kirim)"
                rows="1"
            ></textarea>
            <button type="button" class="btn-send" id="btn-send" title="Kirim pesan">
                <i class="fas fa-paper-plane" id="send-icon"></i>
            </button>
        </div>

    </div>
</div>

{{-- Toast notification --}}
<div id="chat-toast"
     style="display:none; position:fixed; bottom:1.5rem; right:1.5rem;
            background:var(--bg-card); border:1px solid var(--border); border-radius:12px;
            padding:.75rem 1.1rem; box-shadow:0 8px 32px rgba(0,0,0,.3); z-index:9999;
            align-items:center; gap:.65rem; min-width:220px; font-size:.85rem;">
    <i class="fas fa-info-circle" style="color:var(--primary)"></i>
    <span id="toast-msg">–</span>
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
        this.style.height = Math.min(this.scrollHeight, 140) + 'px';
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
    row.className = 'msg-row outbound msg-sending';
    row.id = tempId;
    row.innerHTML = `
        <div>
            <div class="msg-bubble">${escapeHtml(text).replace(/\n/g, '<br>')}</div>
            <div class="msg-meta"><span>${timeStr}</span><i class="fas fa-clock" style="opacity:.5;font-size:.65rem;"></i></div>
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
                tempEl.querySelector('.msg-meta').innerHTML =
                    `<span>${timeStr}</span><i class="fas fa-check-double msg-check"></i>`;
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
    document.querySelectorAll('#sidebar-conv-list .conv-item').forEach(item => {
        const name = item.dataset.name || '';
        item.style.display = name.includes(q) ? '' : 'none';
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
    toast.style.display = 'flex';
    setTimeout(() => { toast.style.display = 'none'; }, duration);
}
</script>
@endpush
