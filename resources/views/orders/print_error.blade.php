<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Resi Ditolak - Resi Belum Tersedia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #333;
        }

        .error-card {
            max-width: 720px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            border: 1px solid #fee2e2;
        }

        .error-header {
            background-color: #fef2f2;
            padding: 24px;
            text-align: center;
            border-bottom: 1px solid #fee2e2;
        }

        .error-icon {
            width: 56px;
            height: 56px;
            line-height: 56px;
            background-color: #fee2e2;
            color: #dc2626;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            margin-bottom: 12px;
        }

        .order-table {
            font-size: 0.88rem;
        }

        .order-table th {
            background-color: #f9fafb;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: #6b7280;
        }
    </style>
</head>

<body>
    <div class="container py-4">
        <div class="error-card">
            <div class="error-header">
                <div class="error-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h4 class="fw-bold text-danger mb-1">Cetak Resi Ditolak</h4>
                <p class="text-muted small mb-0">Nomor resi belum tersedia atau belum diterbitkan oleh marketplace/kurir.</p>
            </div>

            <div class="p-4">
                <div class="alert alert-warning border-0 bg-warning bg-opacity-10 d-flex align-items-start gap-2 mb-4">
                    <i class="fas fa-info-circle text-warning fs-5 mt-0.5"></i>
                    <div class="small">
                        <strong>Sistem telah mencoba menarik resi secara otomatis</strong> dari API marketplace, namun kurir/marketplace belum meng-<i>generate</i> nomor resi (AWB) untuk pesanan berikut. Harap pastikan pesanan sudah diproses atau lakukan request pickup di Seller Center terlebih dahulu.
                        <div class="mt-2 pt-2 border-top border-warning border-opacity-25 text-danger fw-semibold d-flex align-items-center gap-1">
                            <i class="fas fa-ban"></i> Status pesanan <u>TIDAK</u> diubah menjadi sudah dicetak (Tetap <strong>Belum Print</strong>).
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold text-dark mb-2">
                    Daftar Pesanan Tanpa Resi ({{ $ordersWithoutTracking->count() }} Pesanan):
                </h6>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm align-middle order-table mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40px;" class="text-center">No</th>
                                <th>No. Pesanan / Invoice</th>
                                <th>Toko & Channel</th>
                                <th>Kurir</th>
                                <th class="text-center">Status Resi</th>
                                <th class="text-center">Status Cetak</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ordersWithoutTracking as $idx => $order)
                                <tr>
                                    <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                    <td>
                                        <div class="font-monospace fw-bold text-dark">
                                            {{ $order->invoice_number ?? $order->order_marketplace_id }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border">
                                            {{ $order->store->channel->name ?? 'Lokal' }}
                                        </span>
                                        <small class="text-muted d-block">{{ $order->store->store_name ?? '-' }}</small>
                                    </td>
                                    <td>
                                        <i class="fas fa-truck text-muted me-1 small"></i>{{ $order->courier ?? '—' }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">
                                            <i class="fas fa-times-circle me-1"></i>Belum Ada Resi
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border">
                                            <i class="fas fa-clock me-1"></i>Belum Print
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="if(window.opener || window.history.length <= 1) { window.close(); } else { window.history.back(); }">
                        <i class="fas fa-times me-1"></i> Tutup Jendela Ini
                    </button>
                    <a href="{{ route('orders.index') }}" class="btn btn-primary btn-sm px-3">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Pesanan
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
