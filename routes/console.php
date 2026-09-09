<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

// Heartbeat monitor scheduler: mencatat waktu eksekusi crontab secara realtime
Schedule::call(function () {
    \Illuminate\Support\Facades\Cache::forever('marketplace_cron_last_run', now()->toIso8601String());
})->everyMinute();

// 🚀 SINKRONISASI RESI & STATUS PESANAN AKTIF SECARA OTOMATIS (SHOPEE & TIKTOK)
// Menarik nomor resi yang baru dibuat di Seller Center dan update status ke Telah Diproses tanpa klik manual
Schedule::command('marketplace:sync-tracking --days=14 --limit=200')
    ->everyFiveMinutes()
    ->withoutOverlapping(15);

Schedule::command('shopee:refresh-tokens')->everyFifteenMinutes();
Schedule::command('shopee:sync-orders')->everyFifteenMinutes();
Schedule::command('shopee:sync-returns')->everyFifteenMinutes();
Schedule::command('shopee:sync-escrow')->everyThirtyMinutes()->withoutOverlapping();
Schedule::command('shopee-ads:sync --days=3')->dailyAt('01:00')->withoutOverlapping();
Schedule::command('tiktok-ads:sync --days=3')->dailyAt('01:30')->withoutOverlapping();

Schedule::command('tiktok:refresh-tokens')->everyFifteenMinutes();
Schedule::command('tiktok:sync-orders')->everyFifteenMinutes();
Schedule::command('tiktok:sync-escrow')->everyThirtyMinutes()->withoutOverlapping();

// Sinkronisasi chat masuk dari marketplace setiap 5 menit
// [DIMATIKAN] Aktifkan kembali jika fitur chat dipakai
// Schedule::job(new \App\Jobs\PullChatsFromShopee())->everyFiveMinutes();
// Schedule::job(new \App\Jobs\PullChatsFromTiktok())->everyFiveMinutes();

// Cek stok menipis setiap hari jam 08:00
Schedule::command('stock:check-low')->dailyAt('08:00')->withoutOverlapping();

// Auto-atribusi pesanan ke campaign iklan setiap jam (setelah sync order selesai)
Schedule::command('marketing:auto-attribute')->hourly()->withoutOverlapping();

// Evaluasi budget rules setiap jam
Schedule::command('ads:evaluate-rules')->hourly()->withoutOverlapping();

// Sinkronisasi beda stok marketplace otomatis setiap 15 menit (KILAT & TANPA PENUMPUKAN RAM)
Schedule::command('stock:sync --filter=diff')->everyFifteenMinutes()->withoutOverlapping(30);

// Sinkronisasi saldo & mutasi dompet marketplace otomatis setiap jam
Schedule::command('marketplace:sync-wallets --days=15')->hourly()->withoutOverlapping();


