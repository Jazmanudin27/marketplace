@extends('layouts.app')
@section('title', 'Inbox Chat')
@section('page-title', 'Inbox Chat')

@push('styles')
    <style>
        /* ── Chat Layout ── */
        .chat-layout {
            display: flex;
            height: 95vh;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.07);
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.2);
        }

        /* ── Left Sidebar ── */
        .chat-sidebar {
            width: 340px;
            min-width: 300px;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255, 255, 255, 0.07);
            background: var(--bg-card, #1a2332);
        }

        .chat-sidebar-header {
            padding: .875rem 1rem .75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.07);
        }

        /* Search */
        .chat-search-wrap {
            position: relative;
        }

        .chat-search-wrap .fa-search {
            position: absolute;
            left: .75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: .78rem;
            pointer-events: none;
        }

        .chat-search-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.09);
            border-radius: 20px;
            padding: .45rem .85rem .45rem 2.2rem;
            color: #e2e8f0;
            font-size: .83rem;
            transition: border-color .2s, background .2s;
            outline: none;
        }

        .chat-search-input:focus {
            border-color: #6366f1;
            background: rgba(255, 255, 255, 0.08);
        }

        .chat-search-input::placeholder {
            color: #475569;
        }

        /* Filter pills */
        .chat-pills {
            display: flex;
            gap: .35rem;
            padding: .6rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.07);
            overflow-x: auto;
            scrollbar-width: none;
            flex-shrink: 0;
        }

        .chat-pills::-webkit-scrollbar {
            display: none;
        }

        .chat-pill {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .22rem .7rem;
            border-radius: 20px;
            font-size: .75rem;
            font-weight: 500;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: transparent;
            color: #94a3b8;
            cursor: pointer;
            white-space: nowrap;
            text-decoration: none;
            transition: all .18s;
        }

        .chat-pill:hover,
        .chat-pill.active {
            background: #6366f1;
            border-color: #6366f1;
            color: #fff;
        }

        /* Conversation list */
        .chat-conv-list {
            flex: 1;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.08) transparent;
        }

        .chat-conv-list::-webkit-scrollbar {
            width: 4px;
        }

        .chat-conv-list::-webkit-scrollbar-track {
            background: transparent;
        }

        .chat-conv-list::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }

        .conv-item {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .8rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            cursor: pointer;
            text-decoration: none;
            color: #e2e8f0;
            transition: background .15s;
            position: relative;
        }

        .conv-item:hover {
            background: rgba(255, 255, 255, 0.04);
            color: #e2e8f0;
            text-decoration: none;
        }

        .conv-item.active {
            background: rgba(99, 102, 241, 0.12);
        }

        .conv-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: #6366f1;
            border-radius: 0 2px 2px 0;
        }

        /* Avatar */
        .conv-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .95rem;
            color: #fff;
            flex-shrink: 0;
            position: relative;
        }

        .channel-dot {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid var(--bg-card, #1a2332);
            font-size: .5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: #fff;
        }

        .channel-dot.shopee {
            background: #ee4d2d;
        }

        .channel-dot.tiktok {
            background: #010101;
            border-color: #252525;
        }

        .conv-info {
            flex: 1;
            min-width: 0;
        }

        .conv-name {
            font-size: .86rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: .1rem;
        }

        .conv-preview {
            font-size: .74rem;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .conv-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: .25rem;
            flex-shrink: 0;
        }

        .conv-time {
            font-size: .68rem;
            color: #475569;
        }

        /* Status badge */
        .status-badge {
            display: inline-block;
            padding: .08rem .45rem;
            border-radius: 10px;
            font-size: .66rem;
            font-weight: 600;
        }

        .status-open {
            background: rgba(34, 197, 94, .15);
            color: #22c55e;
        }

        .status-closed {
            background: rgba(100, 116, 139, .15);
            color: #94a3b8;
        }

        .status-archived {
            background: rgba(71, 85, 105, .15);
            color: #64748b;
        }

        /* Channel tag */
        .channel-tag {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .18rem .55rem;
            border-radius: 12px;
            font-size: .72rem;
            font-weight: 600;
        }

        .channel-shopee {
            background: rgba(238, 77, 45, .18);
            color: #ee4d2d;
        }

        .channel-tiktok {
            background: rgba(1, 1, 1, .35);
            color: #69c9d0;
            border: 1px solid rgba(105, 201, 208, .25);
        }

        /* Sync bar */
        .chat-sync-bar {
            padding: .5rem .875rem;
            border-top: 1px solid rgba(255, 255, 255, 0.07);
            display: flex;
            gap: .4rem;
            flex-wrap: wrap;
            align-items: center;
            background: rgba(0, 0, 0, 0.15);
            flex-shrink: 0;
        }

        /* Pagination */
        .chat-pagination {
            padding: .4rem .875rem;
            border-top: 1px solid rgba(255, 255, 255, 0.07);
            display: flex;
            justify-content: center;
            flex-shrink: 0;
        }

        .chat-pagination .pagination {
            transform: scale(.82);
            transform-origin: center;
            margin: 0;
        }

        /* ── Right Panel ── */
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .875rem;
            background: var(--bg-card, #1a2332);
            color: #475569;
        }

        .chat-empty-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: #6366f1;
        }

        /* Spinning */
        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .spin {
            animation: spin 1s linear infinite;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .chat-layout {
                height: auto;
                flex-direction: column;
            }

            .chat-sidebar {
                width: 100%;
                min-width: 0;
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.07);
                max-height: 55vh;
            }

            .chat-main {
                min-height: 180px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="chat-layout">

        {{-- ─────────────── LEFT: Daftar Percakapan ─────────────── --}}
        <div class="chat-sidebar">

            {{-- Header & Search --}}
            <div class="chat-sidebar-header">
                <h6 class="mb-2 fw-bold d-flex align-items-center gap-2">
                    <i class="fas fa-comments text-primary"></i> Inbox Chat
                </h6>
                <form method="GET" action="{{ route('chats.index') }}" id="chatFilterForm">
                    <div class="chat-search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" id="chatSearchInput" name="search" value="{{ $search }}"
                            placeholder="Cari buyer atau pesan…" autocomplete="off" class="chat-search-input">
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
            <div class="chat-pills">
                <a href="{{ route('chats.index') }}" class="chat-pill {{ !$channelCode && !$status ? 'active' : '' }}">
                    <i class="fas fa-inbox"></i> Semua
                </a>
                <a href="{{ route('chats.index', array_merge(request()->query(), ['channel' => 'shopee'])) }}"
                    class="chat-pill {{ $channelCode === 'shopee' ? 'active' : '' }}">
                    🛒 Shopee
                </a>
                <a href="{{ route('chats.index', array_merge(request()->query(), ['channel' => 'tiktok'])) }}"
                    class="chat-pill {{ $channelCode === 'tiktok' ? 'active' : '' }}">
                    🎵 TikTok
                </a>
                <a href="{{ route('chats.index', array_merge(request()->query(), ['status' => 'open'])) }}"
                    class="chat-pill {{ $status === 'open' ? 'active' : '' }}">
                    <i class="fas fa-circle" style="font-size:.5rem;color:#22c55e"></i> Open
                </a>
                <a href="{{ route('chats.index', array_merge(request()->query(), ['status' => 'closed'])) }}"
                    class="chat-pill {{ $status === 'closed' ? 'active' : '' }}">
                    <i class="fas fa-check-circle" style="font-size:.5rem"></i> Closed
                </a>
            </div>

            {{-- Conversation List --}}
            <div class="chat-conv-list">
                @forelse($conversations as $conv)
                    @php
                        $ch = $conv->store->channel->code ?? '';
                        $initial = strtoupper(substr($conv->buyer_name ?? 'B', 0, 1));
                        $grad =
                            $ch === 'shopee'
                                ? 'linear-gradient(135deg,#ee4d2d,#f97316)'
                                : ($ch === 'tiktok'
                                    ? 'linear-gradient(135deg,#1d1d1d,#3d3d3d)'
                                    : 'linear-gradient(135deg,#6366f1,#8b5cf6)');
                    @endphp
                    <a href="{{ route('chats.show', $conv) }}" class="conv-item">
                        {{-- Avatar --}}
                        <div class="conv-avatar" style="background:{{ $grad }}">
                            {{ $initial }}
                            <span class="channel-dot {{ $ch }}">
                                {{ $ch === 'shopee' ? 'S' : ($ch === 'tiktok' ? 'T' : '?') }}
                            </span>
                        </div>

                        {{-- Info --}}
                        <div class="conv-info">
                            <div class="conv-name">{{ $conv->buyer_name ?? 'Unknown Buyer' }}</div>
                            <div class="conv-preview">
                                <span class="text-muted" style="font-size:.7rem">{{ $conv->store->store_name }}</span>
                                · {{ \Illuminate\Support\Str::limit($conv->last_message_preview ?? '—', 38) }}
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
                    <div class="text-center py-5 px-3">
                        <i class="fas fa-inbox fa-2x mb-3 d-block opacity-25 text-muted"></i>
                        <div class="small text-muted">Belum ada percakapan.</div>
                        <div class="text-muted" style="font-size:.75rem;margin-top:.25rem">
                            Tekan <strong>Sync</strong> untuk menarik data dari marketplace.
                        </div>
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if ($conversations->hasPages())
                <div class="chat-pagination">
                    {{ $conversations->links('pagination::bootstrap-5') }}
                </div>
            @endif

            {{-- Sync Bar --}}
            <div class="chat-sync-bar">
                <span class="text-muted" style="font-size:.72rem"><i class="fas fa-sync-alt me-1"></i>Sync:</span>
                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2"
                    style="font-size:.73rem; border-radius:20px;" id="btnSyncAll">
                    <i class="fas fa-globe me-1"></i>Semua Toko
                </button>
                @foreach ($stores as $store)
                    <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 btn-sync-store"
                        style="font-size:.73rem; border-radius:20px;" data-store-id="{{ $store->id }}"
                        title="Sync {{ $store->store_name }}">
                        <i class="fas fa-store me-1"></i>{{ Str::limit($store->store_name, 13) }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- ─────────────── RIGHT: Empty state ─────────────── --}}
        <div class="chat-main">
            <div class="chat-empty-icon">
                <i class="fas fa-comment-dots"></i>
            </div>
            <h6 class="fw-bold text-light mb-0">Pilih Percakapan</h6>
            <p class="text-muted small text-center mb-1" style="max-width:240px; line-height:1.6">
                Klik nama buyer di sebelah kiri untuk membuka chat dan membalas pesan.
            </p>
            <div class="d-flex gap-2 flex-wrap justify-content-center">
                <span class="channel-tag channel-shopee"><i class="fas fa-shopping-bag"></i> Shopee</span>
                <span class="channel-tag channel-tiktok"><i class="fab fa-tiktok"></i> TikTok Shop</span>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        $(function() {

            // ── Auto-submit search on stop typing ──────────────────────────
            let searchTimer;
            $('#chatSearchInput').on('input', function() {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => {
                    $('#chatFilterForm').submit();
                }, 500);
            });

            // ── Trigger sync (all toko) ─────────────────────────────────────
            $('#btnSyncAll').on('click', function() {
                triggerSync(null);
            });

            // ── Trigger sync (per toko) ─────────────────────────────────────
            $(document).on('click', '.btn-sync-store', function() {
                triggerSync($(this).data('store-id'));
            });

            // ── Core sync function ──────────────────────────────────────────
            function triggerSync(storeId) {
                // Show SweetAlert loading
                Swal.fire({
                    title: 'Sinkronisasi Chat…',
                    text: storeId ? 'Menarik chat untuk toko ini…' : 'Menarik semua chat marketplace…',
                    icon: 'info',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    background: '#151f2c',
                    color: '#f8fafc',
                    customClass: {
                        popup: 'border border-secondary border-opacity-10'
                    },
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                if (storeId) formData.append('store_id', storeId);

                $.ajax({
                    url: '{{ route('chats.sync') }}',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'Accept': 'application/json'
                    },
                    success: function(data) {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Sync Berhasil!',
                                text: data.message || 'Chat berhasil disinkronisasi.',
                                timer: 2000,
                                showConfirmButton: false,
                                background: '#151f2c',
                                color: '#f8fafc',
                                customClass: {
                                    popup: 'border border-secondary border-opacity-10'
                                }
                            }).then(() => window.location.reload());
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal Sync',
                                text: data.message || 'Terjadi kesalahan saat sinkronisasi.',
                                background: '#151f2c',
                                color: '#f8fafc',
                                customClass: {
                                    popup: 'border border-secondary border-opacity-10'
                                }
                            });
                        }
                    },
                    error: function(xhr) {
                        const msg = xhr.responseJSON?.message ||
                            'Koneksi error atau terjadi kesalahan server.';
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Sync',
                            text: msg,
                            background: '#151f2c',
                            color: '#f8fafc',
                            customClass: {
                                popup: 'border border-secondary border-opacity-10'
                            }
                        });
                    }
                });
            }
        });
    </script>
@endpush
