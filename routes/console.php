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
    ->runInBackground()
    ->withoutOverlapping(15);

// Refresh Token Marketplace (Cepat, jalankan di background)
Schedule::command('shopee:refresh-tokens')
    ->everyFifteenMinutes()
    ->runInBackground()
    ->withoutOverlapping(10);

Schedule::command('tiktok:refresh-tokens')
    ->everyFifteenMinutes()
    ->runInBackground()
    ->withoutOverlapping(10);

// Sinkronisasi Pesanan Rutin (Cukup 2 hari ke belakang agar kilat, hemat CPU & memori)
Schedule::command('shopee:sync-orders --days=2')
    ->everyFifteenMinutes()
    ->runInBackground()
    ->withoutOverlapping(20);

Schedule::command('tiktok:sync-orders --days=2')
    ->everyFifteenMinutes()
    ->runInBackground()
    ->withoutOverlapping(20);

// Deep Sync Pesanan 90 Hari (Hanya 1x sehari di malam hari saat traffic sepi)
Schedule::command('shopee:sync-orders --days=90')
    ->dailyAt('02:00')
    ->runInBackground()
    ->withoutOverlapping(60);

Schedule::command('tiktok:sync-orders --days=90')
    ->dailyAt('02:30')
    ->runInBackground()
    ->withoutOverlapping(60);

// Retur & Escrow
Schedule::command('shopee:sync-returns')
    ->everyFifteenMinutes()
    ->runInBackground()
    ->withoutOverlapping(15);

Schedule::command('shopee:sync-escrow')
    ->everyThirtyMinutes()
    ->runInBackground()
    ->withoutOverlapping(25);

Schedule::command('tiktok:sync-escrow')
    ->everyThirtyMinutes()
    ->runInBackground()
    ->withoutOverlapping(25);

// Iklan Marketplace [DINONAKTIFKAN KARENA TIDAK DIGUNAKAN]
// Schedule::command('shopee-ads:sync --days=3')
//     ->dailyAt('01:00')
//     ->runInBackground()
//     ->withoutOverlapping(30);

// Schedule::command('tiktok-ads:sync --days=3')
//     ->dailyAt('01:30')
//     ->runInBackground()
//     ->withoutOverlapping(30);

// Cek stok menipis setiap hari jam 08:00
Schedule::command('stock:check-low')
    ->dailyAt('08:00')
    ->runInBackground()
    ->withoutOverlapping(15);

// Auto-atribusi & Evaluasi rules iklan [DINONAKTIFKAN KARENA TIDAK DIGUNAKAN]
// Schedule::command('marketing:auto-attribute')
//     ->hourly()
//     ->runInBackground()
//     ->withoutOverlapping(30);

// Schedule::command('ads:evaluate-rules')
//     ->hourly()
//     ->runInBackground()
//     ->withoutOverlapping(20);

// Sinkronisasi beda stok marketplace otomatis setiap 1 menit (KILAT & TANPA PENUMPUKAN RAM)
Schedule::command('stock:sync --filter=diff')
    ->everyMinute()
    ->runInBackground()
    ->withoutOverlapping(10);

// Sinkronisasi saldo & mutasi dompet marketplace otomatis setiap jam
Schedule::command('marketplace:sync-wallets --days=15')
    ->hourly()
    ->runInBackground()
    ->withoutOverlapping(30);

// Proses antrean background jobs (beri jeda rest=1 detik antar-job agar CPU tidak melonjak 100%)
Schedule::command('queue:work --stop-when-empty --max-time=50 --rest=1')
    ->everyMinute()
    ->runInBackground()
    ->withoutOverlapping(10);


