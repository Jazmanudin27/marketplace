<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>🛠️ Secret Admin Maintenance & Data Repair Panel</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts Inter & JetBrains Mono -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-dark: #0f172a;
            --card-bg: #1e293b;
            --card-border: #334155;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent-cyan: #06b6d4;
            --accent-green: #10b981;
            --accent-purple: #8b5cf6;
            --accent-danger: #ef4444;
            --accent-warning: #f59e0b;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            padding-bottom: 3rem;
        }

        .navbar-secret {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--card-border);
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 0.75rem;
            padding: 1.25rem;
            transition: all 0.25s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            border-color: var(--accent-cyan);
            box-shadow: 0 10px 25px -5px rgba(6, 182, 212, 0.15);
        }

        .action-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 0.75rem;
            padding: 1.25rem;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.25s ease;
        }

        .action-card:hover {
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.3);
        }

        .btn-repair {
            border: none;
            font-weight: 600;
            padding: 0.65rem 1.2rem;
            border-radius: 0.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            font-size: 0.85rem;
            width: 100%;
        }

        .btn-cyan {
            background: linear-gradient(135deg, #06b6d4, #0891b2);
            color: #fff;
            box-shadow: 0 4px 12px rgba(6, 182, 212, 0.3);
        }

        .btn-cyan:hover {
            background: linear-gradient(135deg, #0891b2, #0e7490);
            color: #fff;
            box-shadow: 0 6px 16px rgba(6, 182, 212, 0.45);
        }

        .btn-green {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-green:hover {
            background: linear-gradient(135deg, #059669, #047857);
            color: #fff;
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.45);
        }

        .btn-purple {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: #fff;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
        }

        .btn-purple:hover {
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            color: #fff;
            box-shadow: 0 6px 16px rgba(139, 92, 246, 0.45);
        }

        .btn-orange {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: #fff;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
        }

        .btn-orange:hover {
            background: linear-gradient(135deg, #ea580c, #c2410c);
            color: #fff;
            box-shadow: 0 6px 16px rgba(249, 115, 22, 0.45);
        }

        .btn-amber {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #fff;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .btn-amber:hover {
            background: linear-gradient(135deg, #d97706, #b45309);
            color: #fff;
            box-shadow: 0 6px 16px rgba(245, 158, 11, 0.45);
        }

        /* Terminal Console Window */
        .terminal-window {
            background: #090d16;
            border: 1px solid #1e293b;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.5);
        }

        .terminal-header {
            background: #0f172a;
            padding: 0.75rem 1.25rem;
            border-bottom: 1px solid #1e293b;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .terminal-dots {
            display: flex;
            gap: 0.5rem;
        }

        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .dot-red { background: #ff5f56; }
        .dot-yellow { background: #ffbd2e; }
        .dot-green { background: #27c93f; }

        .terminal-body {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.82rem;
            color: #38bdf8;
            padding: 1.25rem;
            min-height: 280px;
            max-height: 480px;
            overflow-y: auto;
            white-space: pre-wrap;
            line-height: 1.6;
        }

        .terminal-body::-webkit-scrollbar {
            width: 6px;
        }
        .terminal-body::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 3px;
        }

        .log-timestamp {
            color: #64748b;
        }
        .log-success {
            color: #4ade80;
        }
        .log-warning {
            color: #fbbf24;
        }
        .log-error {
            color: #f87171;
        }
        .log-info {
            color: #38bdf8;
        }
    </style>
</head>
<body>

    <!-- Header Navbar -->
    <nav class="navbar navbar-dark navbar-secret py-2 mb-4">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-white fs-5" href="#">
                <span class="badge bg-danger rounded-pill fs-6 px-2.5 py-1">SECRET</span>
                <i class="fas fa-tools text-cyan ms-1"></i> Data Maintenance & Repair Dashboard
            </a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted small">
                    <i class="fas fa-user-shield me-1 text-success"></i> Logged in as: <strong>{{ auth()->user()->name ?? 'Admin' }}</strong>
                </span>
                <a href="{{ route('orders.index') }}" class="btn btn-outline-light btn-sm rounded-2">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke ERP
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4">

        <!-- System Stats Row -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold">TOTAL PESANAN ERP</span>
                        <i class="fas fa-shopping-bag text-cyan fs-5"></i>
                    </div>
                    <h3 class="fw-bold text-white mb-0">{{ number_format($ordersCount, 0, ',', '.') }}</h3>
                    <small class="text-muted" style="font-size: 0.72rem;">Database Marketplace Orders</small>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold">ORDER TANPA ITEM</span>
                        <i class="fas fa-exclamation-triangle text-warning fs-5"></i>
                    </div>
                    <h3 class="fw-bold text-warning mb-0" id="statMissingItems">{{ number_format($missingItemsCount, 0, ',', '.') }}</h3>
                    <small class="text-muted" style="font-size: 0.72rem;">Memerlukan Perbaikan Item</small>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold">ORDER UNRECONCILED</span>
                        <i class="fas fa-calculator text-danger fs-5"></i>
                    </div>
                    <h3 class="fw-bold text-danger mb-0" id="statUnreconciled">{{ number_format($unreconciledCount, 0, ',', '.') }}</h3>
                    <small class="text-muted" style="font-size: 0.72rem;">Memerlukan Sync Escrow API</small>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold">TOKO TERHUBUNG</span>
                        <i class="fas fa-store text-success fs-5"></i>
                    </div>
                    <h3 class="fw-bold text-success mb-0">{{ $tiktokStoresCount }} TikTok / {{ $shopeeStoresCount }} Shopee</h3>
                    <small class="text-muted" style="font-size: 0.72rem;">API Channel Connected</small>
                </div>
            </div>
        </div>

        <!-- Action Cards Grid -->
        <h5 class="fw-bold mb-3 text-white"><i class="fas fa-bolt text-warning me-2"></i>Tombol Eksekusi Perbaikan Data & Sync API</h5>

        <div class="row g-3 mb-4">
            
            <!-- Action 1: Fix Missing Items -->
            <div class="col-lg-4 col-md-6">
                <div class="action-card">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-warning bg-opacity-25 text-warning px-2 py-1"><i class="fas fa-boxes me-1"></i>REPAIR ITEM</span>
                            <h6 class="fw-bold text-white mb-0">Perbaiki Item Order Kosong</h6>
                        </div>
                        <p class="text-muted small mb-3" style="font-size: 0.8rem; line-height: 1.4;">
                            Menjalankan perintah <code class="text-warning">php fix_all_missing_items.php</code> untuk memindai pesanan tanpa item dan mengisinya otomatis dari API TikTok/Shopee.
                        </p>
                    </div>
                    <button type="button" class="btn-repair btn-amber" onclick="triggerRepair('fix_missing_items', this)">
                        <i class="fas fa-wrench me-1"></i> Jalankan Perbaikan Item Kosong
                    </button>
                </div>
            </div>

            <!-- Action 2: Sync TikTok Escrow -->
            <div class="col-lg-4 col-md-6">
                <div class="action-card">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-cyan bg-opacity-25 text-cyan px-2 py-1"><i class="fab fa-tiktok me-1"></i>TIKTOK ESCROW</span>
                            <h6 class="fw-bold text-white mb-0">Sync Escrow & Biaya TikTok</h6>
                        </div>
                        <p class="text-muted small mb-3" style="font-size: 0.8rem; line-height: 1.4;">
                            Menjalankan perintah <code class="text-cyan">php artisan tiktok:sync-escrow</code> untuk menarik rincian 5 komisi potongan biaya & dana cair resmi TikTok Shop.
                        </p>
                    </div>
                    <button type="button" class="btn-repair btn-cyan" onclick="triggerRepair('sync_tiktok_escrow', this)">
                        <i class="fas fa-sync-alt me-1"></i> Jalankan Sync Escrow TikTok
                    </button>
                </div>
            </div>

            <!-- Action 3: Sync Shopee Escrow -->
            <div class="col-lg-4 col-md-6">
                <div class="action-card">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-orange bg-opacity-25 text-orange px-2 py-1"><i class="fas fa-shopping-bag me-1"></i>SHOPEE ESCROW</span>
                            <h6 class="fw-bold text-white mb-0">Sync Escrow & Income Shopee</h6>
                        </div>
                        <p class="text-muted small mb-3" style="font-size: 0.8rem; line-height: 1.4;">
                            Menjalankan perintah <code class="text-orange">php artisan shopee:sync-escrow</code> untuk melengkapi potongan rincian saldo cair Shopee Seller Center.
                        </p>
                    </div>
                    <button type="button" class="btn-repair btn-orange" onclick="triggerRepair('sync_shopee_escrow', this)">
                        <i class="fas fa-sync-alt me-1"></i> Jalankan Sync Escrow Shopee
                    </button>
                </div>
            </div>

            <!-- Action 4: Pull TikTok Orders -->
            <div class="col-lg-3 col-md-6">
                <div class="action-card">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-purple bg-opacity-25 text-purple px-2 py-1"><i class="fas fa-download me-1"></i>PULL ORDER</span>
                            <h6 class="fw-bold text-white mb-0">Tarik Pesanan TikTok (7 Hari)</h6>
                        </div>
                        <p class="text-muted small mb-3" style="font-size: 0.8rem;">
                            Menarik dan memperbarui status pesanan terbaru dari TikTok Shop 7 hari terakhir.
                        </p>
                    </div>
                    <button type="button" class="btn-repair btn-purple" onclick="triggerRepair('pull_tiktok_orders', this)">
                        <i class="fas fa-cloud-download-alt me-1"></i> Tarik Pesanan TikTok
                    </button>
                </div>
            </div>

            <!-- Action 5: Pull Shopee Orders -->
            <div class="col-lg-3 col-md-6">
                <div class="action-card">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-orange bg-opacity-25 text-orange px-2 py-1"><i class="fas fa-download me-1"></i>PULL ORDER</span>
                            <h6 class="fw-bold text-white mb-0">Tarik Pesanan Shopee (7 Hari)</h6>
                        </div>
                        <p class="text-muted small mb-3" style="font-size: 0.8rem;">
                            Menarik dan memperbarui status pesanan terbaru dari Shopee Seller Center 7 hari terakhir.
                        </p>
                    </div>
                    <button type="button" class="btn-repair btn-orange" onclick="triggerRepair('pull_shopee_orders', this)">
                        <i class="fas fa-cloud-download-alt me-1"></i> Tarik Pesanan Shopee
                    </button>
                </div>
            </div>

            <!-- Action 6: Recalculate Reconciliation -->
            <div class="col-lg-3 col-md-6">
                <div class="action-card">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-green bg-opacity-25 text-green px-2 py-1"><i class="fas fa-calculator me-1"></i>RECONCILIATION</span>
                            <h6 class="fw-bold text-white mb-0">Hitung Ulang Status Rekonsiliasi</h6>
                        </div>
                        <p class="text-muted small mb-3" style="font-size: 0.8rem;">
                            Mengkalkulasi ulang selisih nilai transaksi dan menandai status MATCHED vs UNRECONCILED.
                        </p>
                    </div>
                    <button type="button" class="btn-repair btn-green" onclick="triggerRepair('recalculate_reconciliation', this)">
                        <i class="fas fa-calculator me-1"></i> Kalkulasi Rekonsiliasi
                    </button>
                </div>
            </div>

            <!-- Action 7: Clear Cache -->
            <div class="col-lg-3 col-md-6">
                <div class="action-card">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-secondary bg-opacity-25 text-light px-2 py-1"><i class="fas fa-broom me-1"></i>CLEAR CACHE</span>
                            <h6 class="fw-bold text-white mb-0">Bersihkan Cache & Memory</h6>
                        </div>
                        <p class="text-muted small mb-3" style="font-size: 0.8rem;">
                            Membersihkan Web Cache, View Cache, dan Memory Rekonsiliasi agar perubahan langsung tercermin.
                        </p>
                    </div>
                    <button type="button" class="btn-repair btn-cyan" onclick="triggerRepair('clear_system_cache', this)">
                        <i class="fas fa-trash-alt me-1"></i> Clear Web Cache
                    </button>
                </div>
            </div>

        </div>

        <!-- Terminal Output Section -->
        <div class="terminal-window mb-4">
            <div class="terminal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="terminal-dots">
                        <div class="dot dot-red"></div>
                        <div class="dot dot-yellow"></div>
                        <div class="dot dot-green"></div>
                    </div>
                    <span class="text-muted font-monospace small"><i class="fas fa-terminal me-1.5 text-cyan"></i>Console Output Output Logs</span>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-dark btn-xs border-secondary text-white py-1 px-2.5 rounded-2" style="font-size: 0.75rem;" onclick="copyConsoleLogs()">
                        <i class="fas fa-copy me-1"></i> Salin Output
                    </button>
                    <button type="button" class="btn btn-dark btn-xs border-secondary text-danger py-1 px-2.5 rounded-2" style="font-size: 0.75rem;" onclick="clearConsoleLogs()">
                        <i class="fas fa-trash me-1"></i> Bersihkan Log
                    </button>
                </div>
            </div>
            <div class="terminal-body" id="consoleOutput">
<span class="log-timestamp">[{{ date('H:i:s') }}]</span> <span class="log-info">Ready! Silakan klik salah satu tombol di atas untuk menjalankan perbaikan data / sync API.</span>
            </div>
        </div>

    </div>

    <!-- Script AJAX Handler -->
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function appendLog(text, type = 'info') {
            const consoleOutput = document.getElementById('consoleOutput');
            const now = new Date().toLocaleTimeString('id-ID');
            let colorClass = 'log-info';
            if (type === 'success') colorClass = 'log-success';
            if (type === 'error') colorClass = 'log-error';
            if (type === 'warning') colorClass = 'log-warning';

            const logLine = document.createElement('div');
            logLine.innerHTML = `<span class="log-timestamp">[${now}]</span> <span class="${colorClass}">${escapeHtml(text)}</span>`;
            consoleOutput.appendChild(logLine);
            consoleOutput.scrollTop = consoleOutput.scrollHeight;
        }

        function escapeHtml(text) {
            return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
        }

        function clearConsoleLogs() {
            document.getElementById('consoleOutput').innerHTML = '<span class="log-timestamp">[' + new Date().toLocaleTimeString('id-ID') + ']</span> <span class="log-info">Log dibersihkan. Sedia menerima instruksi baru.</span>';
        }

        function copyConsoleLogs() {
            const logsText = document.getElementById('consoleOutput').innerText;
            navigator.clipboard.writeText(logsText).then(() => {
                alert('Log berhasil disalin ke clipboard!');
            });
        }

        function triggerRepair(actionName, btnElement) {
            const originalText = btnElement.innerHTML;
            btnElement.disabled = true;
            btnElement.innerHTML = `<i class="fas fa-circle-notch fa-spin me-1"></i> Memproses...`;

            appendLog(`▶ Menjalankan eksekusi: [${actionName}]...`, 'info');

            fetch('{{ route("secret_repair.run") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ action: actionName })
            })
            .then(response => response.json())
            .then(data => {
                btnElement.disabled = false;
                btnElement.innerHTML = originalText;

                if (data.success) {
                    appendLog(`✅ EKSEKUSI [${actionName}] SELESAI dalam ${data.duration}:`, 'success');
                    appendLog(data.output, 'success');

                    if (data.stats) {
                        if (data.stats.missing_items !== undefined) {
                            document.getElementById('statMissingItems').innerText = data.stats.missing_items.toLocaleString('id-ID');
                        }
                        if (data.stats.unreconciled !== undefined) {
                            document.getElementById('statUnreconciled').innerText = data.stats.unreconciled.toLocaleString('id-ID');
                        }
                    }
                } else {
                    appendLog(`❌ EKSEKUSI [${actionName}] GAGAL:`, 'error');
                    appendLog(data.output || data.error, 'error');
                }
            })
            .catch(err => {
                btnElement.disabled = false;
                btnElement.innerHTML = originalText;
                appendLog(`❌ Network Error: ${err.message}`, 'error');
            });
        }
    </script>
</body>
</html>
