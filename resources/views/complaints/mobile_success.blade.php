<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pengaduan Berhasil Dikirim - {{ $tenant->name }}</title>
    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: #333;
        }
        .mobile-container {
            max-width: 500px;
            margin: 0 auto;
            background-color: #ffffff;
            min-height: 100vh;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            position: relative;
        }
        .success-card {
            background: #ffffff;
            border-radius: 1.5rem;
            padding: 3rem 1.5rem;
            text-align: center;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
            animation: bounceIn 0.8s ease;
        }
        .btn-home {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.9rem;
            border-radius: 0.75rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
            text-decoration: none;
            display: block;
            margin-top: 2rem;
        }
        .btn-home:hover {
            opacity: 0.95;
            color: white;
            transform: translateY(-2px);
        }
        @keyframes bounceIn {
            0% {
                transform: scale(0.3);
                opacity: 0;
            }
            50% {
                transform: scale(1.05);
                opacity: 0.8;
            }
            70% {
                transform: scale(0.9);
                opacity: 0.9;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>
</head>
<body>

<div class="mobile-container d-flex flex-column justify-content-center px-4">
    <div class="success-card shadow-sm border">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>
        <h3 class="fw-bold text-dark mb-2">Laporan Dikirim!</h3>
        <p class="text-secondary small mb-4">Terima kasih atas laporan Anda. Laporan pengaduan barang rusak telah kami terima dan akan segera kami proses.</p>
        
        <div class="p-3 bg-light rounded-3 text-start small mb-4">
            <div class="d-flex justify-content-between mb-1.5">
                <span class="text-secondary">Toko Tujuan:</span>
                <span class="fw-bold text-dark">{{ $tenant->name }}</span>
            </div>
            <div class="d-flex justify-content-between mb-1.5">
                <span class="text-secondary">Status Pengaduan:</span>
                <span class="badge bg-warning text-dark rounded-pill fw-semibold">Pending</span>
            </div>
            <div class="text-center text-muted mt-2 border-top pt-2" style="font-size: 0.75rem;">
                Tim kami akan menghubungi Anda via WhatsApp/HP jika laporan telah diproses.
            </div>
        </div>

        <a href="{{ route('complaints.mobile.create', $tenant->id) }}" class="btn-home">
            <i class="fas fa-redo me-1"></i> Kirim Laporan Baru
        </a>
    </div>

    <!-- Footer -->
    <div class="text-center py-4 mt-5 text-muted" style="font-size: 0.8rem;">
        Powered by <span class="fw-semibold text-primary">ASPARTECH</span>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
