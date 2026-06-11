<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$stores = \App\Models\Store::with('channel')->get();
foreach ($stores as $s) {
    echo "ID:{$s->id} | {$s->store_name} | ch:" . ($s->channel->code ?? '?') 
        . " | expires:" . ($s->token_expires_at ?? 'NULL')
        . " | hasToken:" . (!empty($s->getAttributes()['access_token']) ? 'YES' : 'NO')
        . " | hasRefresh:" . (!empty($s->getAttributes()['refresh_token']) ? 'YES' : 'NO')
        . " | status:{$s->status}" . PHP_EOL;
}
