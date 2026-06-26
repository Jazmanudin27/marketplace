<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('shopee:refresh-tokens')->everyFifteenMinutes();
Schedule::command('shopee:sync-orders')->everyFifteenMinutes();
Schedule::command('shopee:sync-returns')->everyFifteenMinutes();

Schedule::command('tiktok:refresh-tokens')->everyFifteenMinutes();
Schedule::command('tiktok:sync-orders')->everyFifteenMinutes();

// Sinkronisasi chat masuk dari marketplace setiap 5 menit
// [DIMATIKAN] Aktifkan kembali jika fitur chat dipakai
// Schedule::job(new \App\Jobs\PullChatsFromShopee())->everyFiveMinutes();
// Schedule::job(new \App\Jobs\PullChatsFromTiktok())->everyFiveMinutes();

// Cek stok menipis setiap hari jam 08:00
Schedule::command('stock:check-low')->dailyAt('08:00')->withoutOverlapping();
