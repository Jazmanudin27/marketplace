<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚡ {{ $flashSale->title }} | Promo Spesial Terbatas</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS Frameworks -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            min-height: 100vh;
        }
        h1, h2, h3, h4, h5, .font-heading {
            font-family: 'Outfit', sans-serif;
        }
        .hero-banner {
            background: linear-gradient(135deg, #dc2626 0%, #7f1d1d 50%, #0f172a 100%);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .glass-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(220, 38, 38, 0.25);
            border-color: rgba(220, 38, 38, 0.4);
        }
        .countdown-box {
            background: rgba(15, 23, 42, 0.85);
            border: 1px solid rgba(239, 68, 68, 0.4);
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.3);
        }
        .discount-badge {
            background: linear-gradient(135deg, #ef4444, #b91c1c);
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .btn-checkout-shopee {
            background: #ee4d2d;
            color: white;
            font-weight: 700;
            border: none;
        }
        .btn-checkout-shopee:hover {
            background: #d73211;
            color: white;
        }
        .btn-checkout-tiktok {
            background: #000000;
            color: white;
            font-weight: 700;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .btn-checkout-tiktok:hover {
            background: #1a1a1a;
            color: white;
        }
    </style>
</head>
<body>

    {{-- Header / Hero Section --}}
    <header class="hero-banner py-5 px-3">
        <div class="container text-center">
            <div class="d-inline-flex align-items-center gap-2 bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50 rounded-pill px-3 py-1 mb-3">
                <i class="bi bi-lightning-charge-fill animate-bounce"></i>
                <span class="fw-bold text-uppercase small" style="letter-spacing: 1px;">Flash Sale Exclusives</span>
            </div>
            <h1 class="display-5 fw-extrabold text-white mb-2">{{ $flashSale->title }}</h1>
            <p class="text-white-50 max-w-2xl mx-auto mb-4">
                Diskon spesial berbatas waktu! Dapatkan produk pilihan dengan harga termurah sebelum kuota kehabisan.
            </p>

            {{-- Countdown Timer Widget --}}
            <div class="d-inline-block countdown-box rounded-4 p-3 px-4 text-center">
                <small class="text-white-50 text-uppercase fw-bold d-block mb-1" style="font-size: .72rem; letter-spacing: 1px;">
                    @if($compStatus === 'ACTIVE')
                        ⚡ PENAWARAN BERAKHIR DALAM:
                    @elseif($compStatus === 'UPCOMING')
                        ⏳ PENAWARAN DIMULAI DALAM:
                    @else
                        🏁 PROMO TELAH BERAKHIR
                    @endif
                </small>
                @if($compStatus === 'ACTIVE' || $compStatus === 'UPCOMING')
                    <div id="public-countdown" data-target="{{ $compStatus === 'ACTIVE' ? $flashSale->end_time->toIso8601String() : $flashSale->start_time->toIso8601String() }}"
                         class="display-6 fw-bold font-monospace text-white">
                        00 : 00 : 00
                    </div>
                @else
                    <div class="fs-4 fw-bold text-secondary">00 : 00 : 00</div>
                @endif
            </div>
        </div>
    </header>

    {{-- Product List Grid --}}
    <main class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold text-white mb-0"><i class="bi bi-fire text-danger me-2"></i>Daftar Produk Promo</h4>
            <button class="btn btn-sm btn-outline-light rounded-pill px-3" onclick="sharePage()">
                <i class="bi bi-share me-1"></i> Bagikan Link
            </button>
        </div>

        @if($items->isEmpty())
            <div class="glass-card text-center py-5">
                <i class="bi bi-box-seam fs-1 text-secondary mb-3 d-block"></i>
                <h5 class="text-white">Belum Ada Produk Didaftarkan</h5>
                <p class="text-muted small">Produk promo akan segera ditampilkan di sini.</p>
            </div>
        @else
            <div class="row g-4">
                @foreach($items as $item)
                    @php
                        $p = $item->masterProduct;
                        $soldRate = $item->sell_through_rate;
                        $isSoldOut = $item->sold_count >= $item->quota;
                    @endphp
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="glass-card h-100 d-flex flex-column overflow-hidden position-relative">
                            {{-- Discount Ribbon --}}
                            <div class="position-absolute top-0 start-0 discount-badge text-white px-3 py-1 rounded-end shadow-sm" style="font-size: .8rem; z-index: 10;">
                                -{{ (int)$item->discount_percentage }}%
                            </div>

                            {{-- Image Placeholder / Real Image --}}
                            <div class="bg-slate-800 text-center py-4 px-3 position-relative" style="background: rgba(15, 23, 42, 0.6); min-height: 180px;">
                                @if($p->image_url)
                                    <img src="{{ $p->image_url }}" alt="{{ $p->name }}" class="img-fluid rounded" style="max-height: 150px; object-fit: contain;">
                                @else
                                    <div class="d-flex flex-column align-items-center justify-content-center h-100 text-secondary">
                                        <i class="bi bi-bag-check fs-1 opacity-50"></i>
                                    </div>
                                @endif
                            </div>

                            <div class="card-body p-3 d-flex flex-column flex-grow-1">
                                <h6 class="fw-bold text-white mb-2 line-clamp-2" style="font-size: 0.95rem; min-height: 2.7em;">
                                    {{ $p->name }}
                                </h6>

                                {{-- Price Breakdown --}}
                                <div class="mb-3">
                                    <div class="text-white-50 text-decoration-line-through small" style="font-size: 0.8rem;">
                                        Rp {{ number_format($item->original_price, 0, ',', '.') }}
                                    </div>
                                    <div class="fs-5 fw-extrabold text-danger">
                                        Rp {{ number_format($item->flash_sale_price, 0, ',', '.') }}
                                    </div>
                                </div>

                                {{-- Stock Progress Bar --}}
                                <div class="mt-auto mb-3">
                                    <div class="d-flex justify-content-between align-items-center small text-white-50 mb-1" style="font-size: 0.7rem;">
                                        <span>Terjual: <strong>{{ $item->sold_count }}</strong></span>
                                        <span>Kuota: {{ $item->quota }}</span>
                                    </div>
                                    <div class="progress bg-slate-800" style="height: 6px;">
                                        <div class="progress-bar bg-danger" role="progressbar" style="width: {{ min(100, $soldRate) }}%"></div>
                                    </div>
                                </div>

                                {{-- Checkout Buttons --}}
                                @if($isSoldOut)
                                    <button class="btn btn-secondary w-100 disabled rounded-3 fw-bold" style="font-size: .85rem;">STOK HABIS</button>
                                @else
                                    <div class="d-grid gap-2">
                                        <a href="https://shopee.co.id" target="_blank" class="btn btn-checkout-shopee btn-sm rounded-3 py-2">
                                            <i class="bi bi-bag-fill me-1"></i> Beli di Shopee
                                        </a>
                                        <a href="https://tiktok.com" target="_blank" class="btn btn-checkout-tiktok btn-sm rounded-3 py-2">
                                            <i class="bi bi-tiktok me-1"></i> Beli di TikTok
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </main>

    {{-- Footer --}}
    <footer class="py-4 text-center text-white-50 border-top border-secondary border-opacity-25 mt-5" style="font-size: .8rem;">
        <div class="container">
            <p class="mb-0">© {{ date('Y') }} ASPARTECH ERP Marketplace. All rights reserved.</p>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const countdownEl = document.getElementById('public-countdown');
        if (countdownEl) {
            const targetDate = new Date(countdownEl.getAttribute('data-target')).getTime();

            function updateTimer() {
                const now = new Date().getTime();
                const diff = targetDate - now;

                if (diff <= 0) {
                    countdownEl.innerHTML = "00 : 00 : 00";
                    return;
                }

                const hours = Math.floor(diff / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                const h = hours.toString().padStart(2, '0');
                const m = minutes.toString().padStart(2, '0');
                const s = seconds.toString().padStart(2, '0');

                countdownEl.innerHTML = `${h} : ${m} : ${s}`;
            }

            updateTimer();
            setInterval(updateTimer, 1000);
        }
    });

    function sharePage() {
        if (navigator.share) {
            navigator.share({
                title: "{{ $flashSale->title }}",
                url: window.location.href
            }).catch(console.error);
        } else {
            navigator.clipboard.writeText(window.location.href);
            alert('Tautan halaman promo berhasil disalin!');
        }
    }
    </script>
</body>
</html>
