<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        \Illuminate\Database\Connection::resolverFor('mysql', function ($connection, $database, $prefix, $config) {
            return new \App\Database\CustomMySqlConnection($connection, $database, $prefix, $config);
        });
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Super Admin, Admin, and Owner bypass for permissions/gates
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return true;
            }
            if ($user->role === 'owner' || $user->hasRole('owner')) {
                return true;
            }
            if ($user->role === 'admin' || $user->hasRole('admin')) {
                if ($ability === 'settings.tenant.edit') {
                    return false;
                }
                return true;
            }
            return null;
        });

        if (str_contains(config('app.url'), 'https://')) {
            if (!app()->runningInConsole() && !in_array(request()->getHost(), ['127.0.0.1', 'localhost'])) {
                URL::forceScheme('https');
            }
        }

        // Jalankan migrasi otomatis jika tabel permission belum ada
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('permissions')) {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            }
        } catch (\Exception $e) {
            // Abaikan error jika database belum siap
        }

        // Kirim notifikasi WA otomatis ke dropshipper jika pesanan online dikirim (SHIPPED)
        \App\Models\Order::updated(function ($order) {
            if ($order->isDirty('order_status') && $order->order_status === \App\Models\Order::STATUS_SHIPPED) {
                if ($order->is_dropship && $order->dropshipper_phone) {
                    $resi = $order->tracking_number ?? 'belum tersedia';
                    $msg = "Halo *{$order->dropshipper_name}*! 👋\n\n"
                        . "Pesanan dropship Anda dengan invoice *#{$order->invoice_number}* (Penerima: {$order->buyer_name}) telah dikirim!\n"
                        . "📦 Kurir: {$order->courier}\n"
                        . "🔖 No. Resi: *{$resi}*\n\n"
                        . "Terima kasih atas kerja samanya! 🙏";

                    \App\Services\WhatsAppService::send($order->dropshipper_phone, $msg);
                }
            }
        });

        // Kirim notifikasi WA otomatis ke dropshipper jika transaksi manual/offline baru dibuat
        \App\Models\OfflineSale::created(function ($sale) {
            if ($sale->is_dropship && $sale->dropshipper_phone) {
                $msg = "Halo *{$sale->dropshipper_name}*! 👋\n\n"
                    . "Transaksi manual dropship Anda *#{$sale->sale_number}* (Penerima: {$sale->buyer_name}) berhasil dicatat!\n"
                    . "💰 Total Pembayaran: Rp " . number_format($sale->grand_total, 0, ',', '.') . "\n"
                    . "💳 Metode: " . $sale->payment_method_label . "\n\n"
                    . "Pesanan Anda sedang dipersiapkan untuk dikirim. Terima kasih! 🙏";

                \App\Services\WhatsAppService::send($sale->dropshipper_phone, $msg);
            }
        });

        // Kirim notifikasi WA otomatis ke dropshipper jika retur diproses (restocked)
        \App\Models\ReturnOrder::updated(function ($returnOrder) {
            if ($returnOrder->isDirty('is_restocked') && $returnOrder->is_restocked) {
                $order = $returnOrder->order;
                if ($order && $order->is_dropship && $order->dropshipper_phone) {
                    $kondisi = $returnOrder->inspection_status === 'GOOD' ? 'Layak Jual (Stok Dikembalikan)' : 'Rusak / Cacat';
                    $catatan = $returnOrder->inspection_notes ? "\nCatatan Inspeksi: " . $returnOrder->inspection_notes : '';
                    
                    $msg = "Halo *{$order->dropshipper_name}*! 👋\n\n"
                        . "Pengembalian barang (retur) dari pembeli Anda *{$order->buyer_name}* dengan No. Retur *{$returnOrder->return_sn}* (Invoice: *#{$order->invoice_number}*) telah kami terima di gudang.\n"
                        . "🔍 Status Inspeksi: *{$kondisi}*{$catatan}\n\n"
                        . "Silakan memproses refund/penukaran barang untuk pelanggan Anda. Terima kasih! 🙏";

                    \App\Services\WhatsAppService::send($order->dropshipper_phone, $msg);
                }
            }
        });
    }
}
