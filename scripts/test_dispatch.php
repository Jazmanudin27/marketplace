<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\App\Jobs\PushStockToMarketplaces::dispatchSync(8, 20); // Push stock to Red
echo "Job executed synchronously!\n";
