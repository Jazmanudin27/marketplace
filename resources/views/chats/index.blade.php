@extends('layouts.app')
@section('title', 'Inbox Chat')
@section('page-title', 'Inbox Chat')

@push('styles')
<style>
/* ============================================================
   CHAT INBOX — TWO-PANEL LAYOUT
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

/* ── LEFT PANEL ── */
.chat-sidebar {
    width: 360px;
    min-width: 320px;
    display: flex;
    flex-direction: column;
    border-right: 1px solid var(--border);
    background: rgba(255,255,255,0.02);
}

.chat-sidebar-header {
    padding: 1.25rem 1.25rem 0.75rem;
    border-bottom: 1px solid var(--border);
}

.chat-sidebar-header h2 {
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0 0 0.75rem;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: .5rem;
}

.chat-search-bar {
    position: relative;
}

.chat-search-bar input {
    width: 100%;
    background: rgba(255,255,255,0.06);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: .5rem 1rem .5rem 2.5rem;
    color: var(--text-primary);
    font-size: .88rem;
    transition: all .2s;
}

.chat-search-bar input:focus {
    outline: none;
    border-color: var(--primary);
    background: rgba(255,255,255,0.09);
}

.chat-search-bar i {
    position: absolute;
    left: .9rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    font-size: .82rem;
}

/* Filter Pills */
.chat-filter-pills {
    display: flex;
    gap: .5rem;
    padding: .75rem 1.25rem;
    border-bottom: 1px solid var(--border);
    overflow-x: auto;
    scrollbar-width: none;
}

.chat-filter-pills::-webkit-scrollbar { display: none; }

.filter-pill {
    display: flex;
    align-items: center;
    gap: .35rem;
    padding: .3rem .85rem;
    border-radius: 20px;
    font-size: .8rem;
    font-weight: 500;
    border: 1px solid var(--border);
    background: transparent;
    color: var(--text-muted);
    cursor: pointer;
    white-space: nowrap;
    text-decoration: none;
    transition: all .2s;
}

.filter-pill:hover,
.filter-pill.active {
    background: var(--primary);
    border-color: var(--primary);
    color: #fff;
}

/* Conversation List */
.chat-conv-list {
    flex: 1;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--border) transparent;
}

.conv-item {
    display: flex;
    align-items: center;
    gap: .875rem;
    padding: .9rem 1.25rem;
    border-bottom: 1px solid rgba(255,255,255,0.04);
    cursor: pointer;
    text-decoration: none;
    color: var(--text-primary);
    transition: background .15s;
    position: relative;
}

.conv-item:hover { background: rgba(255,255,255,0.04); color: var(--text-primary); }
.conv-item.active { background: rgba(99,102,241,0.12); }
.conv-item.active::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 3px;
    background: var(--primary);
    border-radius: 0 2px 2px 0;
}

.conv-avatar {
    width: 46px; height: 46px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1rem;
    color: #fff;
    flex-shrink: 0;
    position: relative;
}

.conv-avatar-img {
    width: 46px; height: 46px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

.channel-dot {
    position: absolute;
    bottom: -2px; right: -2px;
    width: 18px; height: 18px;
    border-radius: 50%;
    border: 2px solid var(--bg-card);
    font-size: .55rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: #fff;
}

.channel-dot.shopee { background: #ee4d2d; }
.channel-dot.tiktok { background: #010101; }

.conv-info { flex: 1; min-width: 0; }

.conv-name {
    font-size: .9rem;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: .15rem;
}

.conv-preview {
    font-size: .78rem;
    color: var(--text-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.conv-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: .3rem;
    flex-shrink: 0;
}

.conv-time {
    font-size: .72rem;
    color: var(--text-muted);
}

.conv-unread {
    width: 20px; height: 20px;
    border-radius: 50%;
    background: var(--primary);
    color: #fff;
    font-size: .65rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Store sync bar */
.sync-bar {
    padding: .6rem 1.25rem;
    border-top: 1px solid var(--border);
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
    align-items: center;
    background: rgba(0,0,0,.15);
}

.sync-bar-label {
    font-size: .75rem;
    color: var(--text-muted);
    margin-right: .25rem;
}

.btn-sync-store {
    display: flex;
    align-items: center;
    gap: .35rem;
    padding: .25rem .7rem;
    border-radius: 20px;
    font-size: .75rem;
    font-weight: 500;
    border: 1px solid var(--border);
    background: transparent;
    color: var(--text-muted);
    cursor: pointer;
    transition: all .2s;
}

.btn-sync-store:hover {
    border-color: var(--primary);
    color: var(--primary);
}

/* ── RIGHT PANEL (empty state) ── */
.chat-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
    gap: 1rem;
}

.chat-empty-icon {
    width: 80px; height: 80px;
    border-radius: 50%;
    background: rgba(99,102,241,0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: var(--primary);
}

.chat-empty-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-primary);
}

.chat-empty-sub {
    font-size: .88rem;
    text-align: center;
    max-width: 260px;
    line-height: 1.6;
}

/* Pagination small */
.chat-pagination {
    padding: .5rem 1.25rem;
    border-top: 1px solid var(--border);
    display: flex;
    justify-content: center;
}

.chat-pagination nav { transform: scale(.85); transform-origin: center; }

/* Channel tag */
.channel-tag {
    display: inline-flex;
    align-items: center;
    gap: .25rem;
    padding: .15rem .55rem;
    border-radius: 12px;
    font-size: .72rem;
    font-weight: 600;
    letter-spacing: .02em;
}

.channel-shopee { background: rgba(238,77,45,.18); color: #ee4d2d; }
.channel-tiktok  { background: rgba(1,1,1,.35); color: #69c9d0; border: 1px solid rgba(105,201,208,.3); }

/* Status badge */
.status-badge {
    display: inline-block;
    padding: .1rem .5rem;
    border-radius: 10px;
    font-size: .7rem;
    font-weight: 600;
}
.status-open     { background: rgba(34,197,94,.15); color: #22c55e; }
.status-closed   { background: rgba(100,116,139,.15); color: #94a3b8; }
.status-archived { background: rgba(71,85,105,.15); color: #64748b; }

/* Spinning icon for loading */
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.spin { animation: spin 1s linear infinite; }

/* Responsive */
@media (max-width: 768px) {
    .chat-layout { height: auto; flex-direction: column; }
    .chat-sidebar { width: 100%; min-width: 0; border-right: none; border-bottom: 1px solid var(--border); height: 55vh; }
    .chat-main { height: 200px; }
}
</style>
@endpush

@section('content')
<div class="chat-layout">

    {{-- ────────────── LEFT: Conversation List ────────────── --}}
    <div class="chat-sidebar">
        <div class="chat-sidebar-header">
            <h2><i class="fas fa-comments" style="color:var(--primary)"></i> Inbox Chat</h2>

            {{-- Search --}}
            <form method="GET" action="{{ route('chats.index') }}" id="chat-filter-form">
                <div class="chat-search-bar mb-2">
                    <i class="fas fa-search"></i>
                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Cari buyer atau pesan..."
                        autocomplete="off"
                        onchange="this.form.submit()"
                    >
                </div>

                {{-- Hidden filters --}}
                @if ($storeId)
                    <input type="hidden" name="store_id" value="{{ $storeId }}">
                @endif
                @if ($channelCode)
                    <input type="hidden" name="channel" value="{{ $channelCode }}">
                @endif
                @if ($status)
                    <input type="hidden" name="status" value="{{ $status }}">
                @endif
            </form>
        </div>

        {{-- Filter Pills --}}
        <div class="chat-filter-pills">
            <a href="{{ route('chats.index') }}"
               class="filter-pill {{ !$channelCode && !$status ? 'active' : '' }}">
                <i class="fas fa-inbox"></i> Semua
            </a>
            <a href="{{ route('chats.index', array_merge(request()->query(), ['channel' => 'shopee'])) }}"
               class="filter-pill {{ $channelCode === 'shopee' ? 'active' : '' }}">
                🛒 Shopee
            </a>
            <a href="{{ route('chats.index', array_merge(request()->query(), ['channel' => 'tiktok'])) }}"
               class="filter-pill {{ $channelCode === 'tiktok' ? 'active' : '' }}">
                🎵 TikTok
            </a>
            <a href="{{ route('chats.index', array_merge(request()->query(), ['status' => 'open'])) }}"
               class="filter-pill {{ $status === 'open' ? 'active' : '' }}">
                <i class="fas fa-circle" style="font-size:.55rem;color:#22c55e"></i> Open
            </a>
            <a href="{{ route('chats.index', array_merge(request()->query(), ['status' => 'closed'])) }}"
               class="filter-pill {{ $status === 'closed' ? 'active' : '' }}">
                <i class="fas fa-check-circle" style="font-size:.55rem"></i> Closed
            </a>
        </div>

        {{-- Conversation List --}}
        <div class="chat-conv-list">
            @forelse($conversations as $conv)
                @php
                    $ch = $conv->store->channel->code ?? '';
                    $initial = strtoupper(substr($conv->buyer_name ?? 'B', 0, 1));
                    $isActive = false; // on index page, no active
                @endphp
                <a href="{{ route('chats.show', $conv) }}" class="conv-item {{ $isActive ? 'active' : '' }}">
                    {{-- Avatar --}}
                    <div class="conv-avatar" style="background: linear-gradient(135deg,
                        {{ $ch === 'shopee' ? '#ee4d2d, #f97316' : ($ch === 'tiktok' ? '#010101, #1d1d1d' : '#6366f1, #8b5cf6') }});">
                        {{ $initial }}
                        <span class="channel-dot {{ $ch }}">
                            {{ $ch === 'shopee' ? 'S' : ($ch === 'tiktok' ? 'T' : '?') }}
                        </span>
                    </div>

                    {{-- Info --}}
                    <div class="conv-info">
                        <div class="conv-name">{{ $conv->buyer_name ?? 'Unknown Buyer' }}</div>
                        <div class="conv-preview">
                            <span style="color:var(--text-muted);font-size:.72rem;">{{ $conv->store->store_name }}</span>
                            · {{ \Illuminate\Support\Str::limit($conv->last_message_preview ?? '—', 40) }}
                        </div>
                    </div>

                    {{-- Meta --}}
                    <div class="conv-meta">
                        <span class="conv-time">
                            {{ optional($conv->last_message_at)->diffForHumans(['short' => true]) ?? '—' }}
                        </span>
                        <span class="status-badge status-{{ $conv->status }}">{{ ucfirst($conv->status) }}</span>
                    </div>
                </a>
            @empty
                <div style="padding:2.5rem 1.5rem; text-align:center; color:var(--text-muted);">
                    <i class="fas fa-inbox" style="font-size:2rem; margin-bottom:.75rem; display:block; opacity:.4;"></i>
                    <div style="font-size:.88rem;">Belum ada percakapan.</div>
                    <div style="font-size:.78rem; margin-top:.35rem;">Tekan Sync untuk menarik data dari marketplace.</div>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($conversations->hasPages())
        <div class="chat-pagination">
            {{ $conversations->links() }}
        </div>
        @endif

        {{-- Sync Bar --}}
        <div class="sync-bar">
            <span class="sync-bar-label"><i class="fas fa-sync-alt"></i> Sync:</span>
            <button type="button"
                    class="btn-sync-store"
                    onclick="triggerSync(null)"
                    id="btn-sync-all">
                <i class="fas fa-globe"></i> Semua Toko
            </button>
            @foreach($stores as $store)
                <button type="button"
                        class="btn-sync-store"
                        onclick="triggerSync({{ $store->id }})"
                        title="Sync {{ $store->store_name }}">
                    <i class="fas fa-store"></i> {{ Str::limit($store->store_name, 14) }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- ────────────── RIGHT: Empty state (pilih percakapan) ────────────── --}}
    <div class="chat-main">
        <div class="chat-empty-icon">
            <i class="fas fa-comment-dots"></i>
        </div>
        <div class="chat-empty-title">Pilih Percakapan</div>
        <div class="chat-empty-sub">
            Klik nama buyer di sebelah kiri untuk membuka chat dan membalas pesan.
        </div>
        <div style="display:flex; gap:.75rem; flex-wrap:wrap; justify-content:center; margin-top:.5rem;">
            <span class="channel-tag channel-shopee"><i class="fas fa-shopping-bag"></i> Shopee</span>
            <span class="channel-tag channel-tiktok"><i class="fab fa-tiktok"></i> TikTok Shop</span>
        </div>
    </div>

</div>

{{-- Sync toast --}}
<div id="sync-toast"
     style="display:none; position:fixed; bottom:1.5rem; right:1.5rem; background:var(--bg-card);
            border:1px solid var(--border); border-radius:12px; padding:.875rem 1.25rem;
            box-shadow:0 8px 32px rgba(0,0,0,.3); z-index:9999; display:flex; align-items:center; gap:.75rem; min-width:260px;">
    <i class="fas fa-sync-alt spin" style="color:var(--primary)"></i>
    <span style="font-size:.88rem; color:var(--text-primary);">Sync chat berjalan di latar belakang…</span>
</div>
@endsection

@push('scripts')
<script>
function triggerSync(storeId) {
    const toast = document.getElementById('sync-toast');
    const toastText = toast.querySelector('span');
    const toastIcon = toast.querySelector('i');
    
    // Set loading state
    toastIcon.className = 'fas fa-sync-alt spin';
    toastIcon.style.color = 'var(--primary)';
    toastText.textContent = 'Sync chat sedang berjalan...';
    toast.style.display = 'flex';

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
            toastIcon.className = 'fas fa-check-circle text-success';
            toastIcon.style.color = '#22c55e';
            toastText.textContent = d.message || 'Sync berhasil!';
            setTimeout(() => {
                toast.style.display = 'none';
                window.location.reload();
            }, 1200);
        } else {
            toastIcon.className = 'fas fa-exclamation-circle text-danger';
            toastIcon.style.color = '#ef4444';
            toastText.textContent = d.message || 'Gagal sinkronisasi';
            // Biarkan user membaca error lebih lama
            setTimeout(() => { toast.style.display = 'none'; }, 8000);
        }
    })
    .catch((err) => {
        toastIcon.className = 'fas fa-exclamation-circle text-danger';
        toastIcon.style.color = '#ef4444';
        toastText.textContent = 'Koneksi error atau gagal sinkronisasi.';
        setTimeout(() => { toast.style.display = 'none'; }, 5000);
    });
}
</script>
@endpush
