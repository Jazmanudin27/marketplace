<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MasterProduct;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

$user = \App\Models\User::first();
if ($user) {
    Auth::login($user);
    $tenantId = $user->tenant_id;
} else {
    $tenantId = 1;
}

echo "Starting benchmark...\n";

$t1 = microtime(true);
$categories = Category::where('tenant_id', $tenantId)->orderBy('name')->get();
$brands = Brand::where('tenant_id', $tenantId)->orderBy('name')->get();
$stores = Store::with('channel')->where('tenant_id', $tenantId)->where('status', 'connected')->get();
echo "Base metadata fetched in " . round(microtime(true) - $t1, 3) . "s\n";

$t2 = microtime(true);
$query = MasterProduct::where('tenant_id', $tenantId);
$totalCount = (clone $query)->count();
$bundleCount = (clone $query)->where('is_bundle', true)->count();
$singleCount = $totalCount - $bundleCount;
$totalStockValue = (clone $query)->sum(DB::raw('stock * cost_price'));
echo "Stats calculated in " . round(microtime(true) - $t2, 3) . "s (Total products: $totalCount)\n";

$t3 = microtime(true);
// Test fetching raw products without relations
$rawProducts = MasterProduct::where('tenant_id', $tenantId)->orderBy('is_bundle', 'desc')->orderBy('name', 'asc')->get();
echo "Raw products fetched in " . round(microtime(true) - $t3, 3) . "s\n";

$t4 = microtime(true);
// Test loading with relations
$rawProducts->load(['category', 'brand', 'components', 'marketplaceProducts.store.channel']);
echo "Relations eager loaded in " . round(microtime(true) - $t4, 3) . "s\n";

echo "Peak Memory: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB\n";
