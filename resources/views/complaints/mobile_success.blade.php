<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Laporan Terkirim — {{ $tenant->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --success-g: #10b981;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #0f0f1a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .mobile-wrap {
            max-width: 430px;
            width: 100%;
            min-height: 100vh;
            background: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
            position: relative;
            overflow: hidden;
        }

        /* ─── Background blobs ─── */
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            pointer-events: none;
            z-index: 0;
        }
        .blob-1 {
            width: 280px; height: 280px;
            background: rgba(99,102,241,0.08);
            top: -60px; left: -60px;
        }
        .blob-2 {
            width: 220px; height: 220px;
            background: rgba(16,185,129,0.08);
            bottom: -40px; right: -40px;
        }

        /* ─── Content ─── */
        .content { position: relative; z-index: 1; text-align: center; width: 100%; }

        /* ─── Success Animation ─── */
        .check-wrap {
            width: 90px; height: 90px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981, #059669);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            color: #fff;
            margin-bottom: 1.75rem;
            box-shadow: 0 12px 30px rgba(16,185,129,0.35);
            animation: popIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
        }
        @keyframes popIn {
            from { transform: scale(0); opacity: 0; }
            to   { transform: scale(1); opacity: 1; }
        }

        .success-title {
            font-size: 1.65rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.03em;
            margin-bottom: 0.5rem;
        }
        .success-sub {
            font-size: 0.88rem;
            color: #64748b;
            line-height: 1.6;
            max-width: 280px;
            margin: 0 auto 2rem;
        }

        /* ─── Status Card ─── */
        .status-card {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 1.25rem;
            padding: 1.25rem;
            width: 100%;
            margin-bottom: 1.75rem;
            text-align: left;
        }
        .status-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            font-size: 0.83rem;
        }
        .status-row:not(:last-child) { border-bottom: 1px solid #f1f5f9; }
        .status-row .label { color: #64748b; font-weight: 500; }
        .status-row .value { font-weight: 700; color: #0f172a; text-align: right; max-width: 60%; }

        .badge-pending {
            background: #fef3c7;
            color: #d97706;
            padding: 0.25rem 0.65rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        /* ─── Info Note ─── */
        .note {
            background: linear-gradient(135deg, #ede9fe, #ddd6fe);
            border-radius: 0.875rem;
            padding: 0.9rem 1rem;
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
            margin-bottom: 2rem;
            font-size: 0.8rem;
            color: #4338ca;
            text-align: left;
        }
        .note i { font-size: 1rem; margin-top: 1px; flex-shrink: 0; }

        /* ─── Buttons ─── */
        .btn-primary-custom {
            width: 100%;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border: none;
            color: #fff;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.92rem;
            font-weight: 700;
            padding: 0.9rem;
            border-radius: 0.875rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 8px 20px rgba(79,70,229,0.3);
            transition: all 0.3s ease;
            margin-bottom: 0.75rem;
        }
        .btn-primary-custom:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 12px 28px rgba(79,70,229,0.4); }

        .btn-ghost {
            width: 100%;
            background: transparent;
            border: 1.5px solid #e2e8f0;
            color: #64748b;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.88rem;
            font-weight: 600;
            padding: 0.85rem;
            border-radius: 0.875rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            transition: all 0.2s ease;
        }
        .btn-ghost:hover { color: var(--primary); border-color: #a5b4fc; background: #f0f0ff; }

        .footer {
            margin-top: 2rem;
            font-size: 0.73rem;
            color: #94a3b8;
        }
        .footer strong { color: var(--primary); }
    </style>
</head>
<body>

<div class="mobile-wrap">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="content">
        <div class="check-wrap">
            <i class="fas fa-check"></i>
        </div>

        <h1 class="success-title">Laporan Terkirim!</h1>
        <p class="success-sub">
            Terima kasih. Laporan pengaduan barang rusak Anda telah kami terima dengan baik.
        </p>

        <div class="status-card">
            <div class="status-row">
                <span class="label"><i class="fas fa-store me-1 text-primary" style="font-size:0.78rem;"></i> Toko Tujuan</span>
                <span class="value">{{ $tenant->name }}</span>
            </div>
            <div class="status-row">
                <span class="label"><i class="fas fa-clock me-1 text-warning" style="font-size:0.78rem;"></i> Status</span>
                <span class="value"><span class="badge-pending">Pending</span></span>
            </div>
            <div class="status-row">
                <span class="label"><i class="fas fa-calendar me-1 text-muted" style="font-size:0.78rem;"></i> Waktu Laporan</span>
                <span class="value" style="font-size:0.8rem;">{{ now()->locale('id')->isoFormat('D MMM YYYY, HH:mm') }}</span>
            </div>
        </div>

        <div class="note">
            <i class="fab fa-whatsapp"></i>
            <span>Tim kami akan menghubungi Anda melalui <strong>WhatsApp/HP</strong> dalam 1×24 jam untuk menindaklanjuti laporan ini.</span>
        </div>

        <a href="{{ route('complaints.mobile.create', $tenant->id) }}" class="btn-primary-custom">
            <i class="fas fa-file-circle-plus"></i>
            Kirim Laporan Baru
        </a>

    </div>

    <div class="footer text-center">
        Powered by <strong>ASPARTECH</strong> &bull; Sistem ERP Marketplace
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
