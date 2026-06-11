<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

App\Models\Channel::ensureChannelsExist();

$channels = DB::table('channels')->get();

echo "\n=== DAFTAR CHANNEL DI DATABASE ===\n";
foreach ($channels as $c) {
    echo sprintf("ID:%-3s | Code:%-12s | Name:%-15s | Status:%s\n",
        $c->id, $c->code, $c->name, $c->status ? 'Active' : 'Inactive');
}
echo "\nTotal: " . count($channels) . " channel\n";
