<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Store;
use App\Models\User;

echo "=== TENANT INFORMATION ===\n";
foreach (Store::all() as $s) {
    echo "Store ID: {$s->id} | Name: {$s->store_name} | Tenant ID: {$s->tenant_id}\n";
}

echo "\n=== USERS IN SYSTEM ===\n";
foreach (User::all() as $u) {
    echo "User ID: {$u->id} | Name: {$u->name} | Email: {$u->email} | Tenant ID: {$u->tenant_id}\n";
}
