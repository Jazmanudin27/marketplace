@extends('layouts.app')
@section('title', 'Inbox Chat')
@section('page-title', 'Inbox Chat')

@section('content')
<div class="row g-0 border rounded shadow-sm bg-white" style="height: calc(100vh - 160px); overflow: hidden;">
    <!-- LEFT: Conversation List Sidebar -->
    <div class="col-md-4 d-flex flex-column border-end bg-light" style="max-height: 100%;">
        <!-- Header & Search -->
        <div class="p-3 border-bottom bg-white">
            <h6 class="mb-2 fw-bold text-primary">
                <i class="fas fa-comments me-2"></i> Inbox Chat
            </h6>
            <form method="GET" action="{{ route('chats.index') }}" id="chatFilterForm">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 text-muted">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" id="chatSearchInput" name="search" value="{{ $search }}"
                        placeholder="Cari buyer atau pesan…" autocomplete="off" class="form-control border-start-0 bg-light">
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

        <!-- Filter Pills -->
        <div class="d-flex gap-1 p-2 border-bottom overflow-auto bg-white" style="white-space: nowrap;">
            <a href="{{ route('chats.index') }}" class="btn btn-outline-primary btn-sm rounded-pill py-0.5 px-3 {{ !$channelCode && !$status ? 'active' : '' }}">
                <i class="fas fa-inbox me-1"></i> Semua
            </a>
            <a href="{{ route('chats.index', array_merge(request()->query(), ['channel' => 'shopee'])) }}"
                class="btn btn-outline-primary btn-sm rounded-pill py-0.5 px-3 {{ $channelCode === 'shopee' ? 'active' : '' }}">
                🛒 Shopee
            </a>
            <a href="{{ route('chats.index', array_merge(request()->query(), ['channel' => 'tiktok'])) }}"
                class="btn btn-outline-primary btn-sm rounded-pill py-0.5 px-3 {{ $channelCode === 'tiktok' ? 'active' : '' }}">
                🎵 TikTok
            </a>
            <a href="{{ route('chats.index', array_merge(request()->query(), ['status' => 'open'])) }}"
                class="btn btn-outline-primary btn-sm rounded-pill py-0.5 px-3 {{ $status === 'open' ? 'active' : '' }}">
                <i class="fas fa-circle me-1 text-success" style="font-size:.5rem;"></i> Open
            </a>
            <a href="{{ route('chats.index', array_merge(request()->query(), ['status' => 'closed'])) }}"
                class="btn btn-outline-primary btn-sm rounded-pill py-0.5 px-3 {{ $status === 'closed' ? 'active' : '' }}">
                <i class="fas fa-check-circle me-1"></i> Closed
            </a>
        </div>

        <!-- Conversation List -->
        <div class="list-group list-group-flush flex-grow-1 overflow-auto bg-white" id="sidebar-conv-list">
            @forelse($conversations as $conv)
                @php
                    $ch = $conv->store->channel->code ?? '';
                    $initial = strtoupper(substr($conv->buyer_name ?? 'B', 0, 1));
                    $bgGrad = $ch === 'shopee' ? 'bg-danger' : ($ch === 'tiktok' ? 'bg-dark' : 'bg-primary');
                @endphp
                <a href="{{ route('chats.show', $conv) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3 border-0 border-bottom p-3">
                    <!-- Avatar -->
                    <div class="position-relative rounded-circle d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0 {{ $bgGrad }}" style="width: 42px; height: 42px; font-size: 0.95rem;">
                        {{ $initial }}
                        <span class="position-absolute bottom-0 end-0 badge rounded-circle p-1 {{ $ch === 'shopee' ? 'bg-warning text-dark' : 'bg-secondary text-white' }}" style="font-size: 0.55rem; width: 18px; height: 18px; transform: translate(20%, 20%);">
                            {{ $ch === 'shopee' ? 'S' : ($ch === 'tiktok' ? 'T' : '?') }}
                        </span>
                    </div>

                    <!-- Info -->
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-bold text-dark small text-truncate mb-0.5">{{ $conv->buyer_name ?? 'Unknown Buyer' }}</div>
                        <div class="text-muted small text-truncate" style="font-size: 0.75rem;">
                            <span class="fw-semibold text-primary">{{ $conv->store->store_name }}</span>
                            · {{ \Illuminate\Support\Str::limit($conv->last_message_preview ?? '—', 38) }}
                        </div>
                    </div>

                    <!-- Meta -->
                    <div class="text-end flex-shrink-0">
                        <div class="text-muted" style="font-size: 0.68rem;">
                            {{ optional($conv->last_message_at)->diffForHumans(['short' => true]) ?? '—' }}
                        </div>
                        <span class="badge {{ $conv->status === 'open' ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary' }} mt-1" style="font-size: 0.65rem;">
                            {{ ucfirst($conv->status) }}
                        </span>
                    </div>
                </a>
            @empty
                <div class="text-center py-5 px-3 bg-white flex-grow-1">
                    <i class="fas fa-inbox fa-2x mb-3 d-block opacity-25 text-muted"></i>
                    <div class="small text-muted">Belum ada percakapan.</div>
                    <div class="text-muted mt-1" style="font-size:.75rem;">
                        Tekan <strong>Sync</strong> untuk menarik data dari marketplace.
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if ($conversations->hasPages())
            <div class="p-2 border-top bg-light d-flex justify-content-center">
                {{ $conversations->links('pagination::bootstrap-5') }}
            </div>
        @endif

        <!-- Sync Bar -->
        <div class="p-2 bg-light border-top d-flex gap-1 align-items-center flex-wrap">
            <span class="text-muted small me-1"><i class="fas fa-sync-alt me-1"></i>Sync:</span>
            <button type="button" class="btn btn-outline-secondary btn-sm py-0.5 px-2.5 rounded-pill" style="font-size:.73rem;" id="btnSyncAll">
                <i class="fas fa-globe me-1"></i>Semua
            </button>
            @foreach ($stores as $store)
                <button type="button" class="btn btn-outline-secondary btn-sm py-0.5 px-2.5 rounded-pill btn-sync-store" style="font-size:.73rem;" data-store-id="{{ $store->id }}" title="Sync {{ $store->store_name }}">
                    <i class="fas fa-store me-1"></i>{{ Str::limit($store->store_name, 13) }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- RIGHT: Empty state -->
    <div class="col-md-8 d-none d-md-flex flex-column align-items-center justify-content-center bg-white text-muted">
        <div class="rounded-circle bg-primary bg-opacity-10 p-4 mb-3 text-primary fs-3 d-flex align-items-center justify-content-center" style="width: 72px; height: 72px;">
            <i class="fas fa-comment-dots"></i>
        </div>
        <h6 class="fw-bold text-dark mb-1">Pilih Percakapan</h6>
        <p class="small text-muted mb-0 text-center" style="max-width:240px; line-height:1.6">
            Klik nama buyer di sebelah kiri untuk membuka chat dan membalas pesan.
        </p>
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
                                showConfirmButton: false
                            }).then(() => window.location.reload());
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal Sync',
                                text: data.message || 'Terjadi kesalahan saat sinkronisasi.'
                            });
                        }
                    },
                    error: function(xhr) {
                        const msg = xhr.responseJSON?.message ||
                            'Koneksi error atau terjadi kesalahan server.';
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Sync',
                            text: msg
                        });
                    }
                });
            }
        });
    </script>
@endpush
