<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Order;

echo "=== SIMULASI DAFTAR PESANAN UNTUK SETIAP USER ===\n";

foreach (User::all() as $user) {
    $tenantId = $user->tenant_id;
    
    $query = Order::where('tenant_id', $tenantId);
    $count = $query->count();
    
    echo "User: {$user->name} ({$user->email}) | Tenant ID: " . ($tenantId ?: 'NULL') . " | Jumlah Order Terlihat: {$count}\n";
}
