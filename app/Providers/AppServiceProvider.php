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
    }
}
