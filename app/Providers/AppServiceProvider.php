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
    }
}
