<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceProduct;
use App\Models\MasterProduct;
use App\Models\Channel;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MarketplaceProductController extends Controller
{
    private function buildProductQuery(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = MarketplaceProduct::with(['store.channel', 'masterProduct'])
            ->whereHas('store', function($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            });

        if ($request->filled('status')) {
            if ($request->status === 'unmapped') {
                $query->whereDoesntHave('masterProduct');
            } elseif ($request->status === 'mapped') {
                $query->whereHas('masterProduct');
            }
        }

        if ($request->filled('name')) {
            $query->where('marketplace_products.name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('sku')) {
            $query->where('marketplace_products.marketplace_sku', 'like', '%' . $request->sku . '%');
        }

        if ($request->filled('channel_id')) {
            $query->whereHas('store', function($q) use ($request) {
                $q->where('channel_id', $request->channel_id);
            });
        }

        if ($request->filled('store_id')) {
            $query->where('marketplace_products.store_id', $request->store_id);
        }

        // Filter: po_status (po / non_po)
        if ($request->filled('po_status')) {
            if ($request->po_status === 'po') {
                $query->where(function($q) {
                    $q->whereHas('masterProduct', function($mq) {
                        $mq->where('is_preorder', true);
                    })
                    ->orWhere(function($sub) {
                        $sub->whereNull('marketplace_products.master_product_id')
                            ->where(function($poq) {
                                $poq->where('marketplace_products.is_pre_order', true)
                                    ->orWhere('marketplace_products.name', 'like', '%PRE ORDER%')
                                    ->orWhere('marketplace_products.name', 'like', '%PREORDER%')
                                    ->orWhere('marketplace_products.name', 'like', '%PRE-ORDER%')
                                    ->orWhere('marketplace_products.name', 'like', 'PO %')
                                    ->orWhere('marketplace_products.name', 'like', '% PO %');
                            });
                    });
                });
            } elseif ($request->po_status === 'non_po') {
                $query->where(function($q) {
                    $q->whereHas('masterProduct', function($mq) {
                        $mq->where('is_preorder', false);
                    })
                    ->orWhere(function($sub) {
                        $sub->whereNull('marketplace_products.master_product_id')
                            ->where('marketplace_products.is_pre_order', false)
                            ->where('marketplace_products.name', 'not like', '%PRE ORDER%')
                            ->where('marketplace_products.name', 'not like', '%PREORDER%')
                            ->where('marketplace_products.name', 'not like', '%PRE-ORDER%')
                            ->where('marketplace_products.name', 'not like', 'PO %')
                            ->where('marketplace_products.name', 'not like', '% PO %');
                    });
                });
            }
        }

        // Filter: sync_status (match / diff)
        if ($request->filled('sync_status')) {
            if ($request->sync_status === 'match') {
                $query->join('master_products', 'marketplace_products.master_product_id', '=', 'master_products.id')
                      ->where('marketplace_products.is_pre_order', false)
                      ->where('master_products.is_preorder', false)
                      ->where('marketplace_products.name', 'not like', '%PRE ORDER%')
                      ->where('marketplace_products.name', 'not like', '%PREORDER%')
                      ->where('marketplace_products.name', 'not like', '%PRE-ORDER%')
                      ->where('marketplace_products.name', 'not like', 'PO %')
                      ->where('marketplace_products.name', 'not like', '% PO %')
                      ->whereRaw('marketplace_products.stock = IF(master_products.stock - COALESCE(marketplace_products.safety_stock, 0) < 0, 0, master_products.stock - COALESCE(marketplace_products.safety_stock, 0))');
            } elseif ($request->sync_status === 'diff') {
                $query->join('master_products', 'marketplace_products.master_product_id', '=', 'master_products.id')
                      ->where('marketplace_products.is_pre_order', false)
                      ->where('master_products.is_preorder', false)
                      ->where('marketplace_products.name', 'not like', '%PRE ORDER%')
                      ->where('marketplace_products.name', 'not like', '%PREORDER%')
                      ->where('marketplace_products.name', 'not like', '%PRE-ORDER%')
                      ->where('marketplace_products.name', 'not like', 'PO %')
                      ->where('marketplace_products.name', 'not like', '% PO %')
                      ->whereRaw('marketplace_products.stock != IF(master_products.stock - COALESCE(marketplace_products.safety_stock, 0) < 0, 0, master_products.stock - COALESCE(marketplace_products.safety_stock, 0))');
            }
        }

        return $query;
    }

    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $query = $this->buildProductQuery($request);

        $marketplaceProducts = $query->select('marketplace_products.*')->latest('marketplace_products.updated_at')->paginate(20)->withQueryString();

        // Ambil data master product untuk dropdown 'Tautkan'
        $masterProducts = MasterProduct::where('tenant_id', $tenantId)->orderBy('name')->get();

        // Ambil data channel dan store untuk filter
        $channels = Channel::orderBy('name')->get();
        $stores = Store::where('tenant_id', $tenantId)->orderBy('store_name')->get();

        return view('marketplace_products.index', compact('marketplaceProducts', 'masterProducts', 'channels', 'stores'));
    }

    public function promote(MarketplaceProduct $product)
    {
        abort_unless($product->store->tenant_id === Auth::user()->tenant_id, 403);
        
        if ($product->masterProduct) {
            return back()->with('error', 'Produk ini sudah ditautkan ke Master Product.');
        }

        if ($product->marketplace_sku) {
            $existingMaster = MasterProduct::where('tenant_id', Auth::user()->tenant_id)
                                ->where('sku', $product->marketplace_sku)
                                ->first();
            
            if ($existingMaster) {
                return back()->with('error', "Gagal! SKU '{$product->marketplace_sku}' sudah terdaftar di Master Produk ('{$existingMaster->name}'). Silakan gunakan tombol 'Tautkan' ke produk tersebut agar tidak terjadi duplikat.");
            }
        }

        try {
            DB::transaction(function () use ($product) {
                $attrs = $this->parseAttributesFromName($product->name);
                $sku = $product->marketplace_sku ?: ('SKU-' . time() . '-' . rand(100, 999));

                // Buat Master Product baru berdasarkan data dari MarketplaceProduct
                $master = MasterProduct::create([
                    'tenant_id' => Auth::user()->tenant_id,
                    'sku' => $sku,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => $product->price,
                    'stock' => $product->stock,
                    'image_url' => $product->image_url,
                    'is_active' => true,
                    'ukuran' => $attrs['ukuran'],
                    'warna' => $attrs['warna'],
                ]);

                // Update marketplace product agar tertaut ke master yang baru
                $product->update([
                    'marketplace_sku' => $sku,
                    'master_product_id' => $master->id,
                ]);
            });

            return back()->with('success', "Berhasil! Produk '{$product->name}' telah dijadikan Master Product.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menjadikan Master Product: ' . $e->getMessage());
        }
    }

    public function link(Request $request, MarketplaceProduct $product)
    {
        abort_unless($product->store->tenant_id === Auth::user()->tenant_id, 403);
        
        $request->validate([
            'master_product_id' => 'required|exists:master_products,id'
        ]);

        $master = MasterProduct::findOrFail($request->master_product_id);
        abort_unless($master->tenant_id === Auth::user()->tenant_id, 403);

        $product->update([
            'marketplace_sku' => $master->sku,
            'master_product_id' => $master->id,
        ]);

        if (empty($master->image_url) && !empty($product->image_url)) {
            $master->update(['image_url' => $product->image_url]);
        }

        // Tautkan atribut ukuran & warna secara otomatis jika di master masih kosong
        $attrs = $this->parseAttributesFromName($product->name);
        $updateData = [];
        if (empty($master->ukuran) && !empty($attrs['ukuran'])) {
            $updateData['ukuran'] = $attrs['ukuran'];
        }
        if (empty($master->warna) && !empty($attrs['warna'])) {
            $updateData['warna'] = $attrs['warna'];
        }
        if (empty($master->description) && !empty($product->description)) {
            $updateData['description'] = $product->description;
        }
        if (!empty($updateData)) {
            $master->update($updateData);
        }

        return back()->with('success', "Produk marketplace '{$product->name}' berhasil ditautkan ke Master '{$master->name}'.");
    }

    public function updateSettings(Request $request, MarketplaceProduct $product)
    {
        abort_unless($product->store->tenant_id === Auth::user()->tenant_id, 403);

        $data = $request->validate([
            'safety_stock' => 'required|integer|min:0',
        ]);

        $syncStock = $request->boolean('sync_stock');
        $syncPrice = $request->boolean('sync_price');

        $product->update([
            'sync_stock' => $syncStock,
            'sync_price' => $syncPrice,
            'safety_stock' => $data['safety_stock'],
        ]);

        if ($syncStock && $product->master_product_id) {
            \App\Jobs\PushStockToMarketplaces::dispatch($product->master_product_id, $product->masterProduct->stock);
        }

        if ($syncPrice && $product->master_product_id) {
            \App\Jobs\PushPriceToMarketplaces::dispatch($product->master_product_id, (float)$product->masterProduct->price);
        }

        return back()->with('success', "Pengaturan sinkronisasi untuk produk '{$product->name}' berhasil diperbarui.");
    }

    public function cloneAndPublish(MarketplaceProduct $product)
    {
        abort_unless($product->store->tenant_id === Auth::user()->tenant_id, 403);

        // Jika sudah tertaut ke master product, langsung arahkan ke halaman publish
        if ($product->master_product_id) {
            return redirect()->route('products.publish', $product->master_product_id);
        }

        // Cek jika SKU sudah ada di master
        if ($product->marketplace_sku) {
            $existingMaster = MasterProduct::where('tenant_id', Auth::user()->tenant_id)
                                ->where('sku', $product->marketplace_sku)
                                ->first();
            
            if ($existingMaster) {
                // Tautkan otomatis ke master yang sudah ada
                $product->update(['master_product_id' => $existingMaster->id]);
                
                // Tautkan atribut ukuran & warna secara otomatis jika di master masih kosong
                $attrs = $this->parseAttributesFromName($product->name);
                $updateData = [];
                if (empty($existingMaster->ukuran) && !empty($attrs['ukuran'])) {
                    $updateData['ukuran'] = $attrs['ukuran'];
                }
                if (empty($existingMaster->warna) && !empty($attrs['warna'])) {
                    $updateData['warna'] = $attrs['warna'];
                }
                if (!empty($updateData)) {
                    $existingMaster->update($updateData);
                }

                return redirect()->route('products.publish', $existingMaster->id)
                    ->with('success', "Produk marketplace otomatis ditautkan ke Master Produk '{$existingMaster->name}' yang memiliki SKU yang sama.");
            }
        }

        try {
            $master = DB::transaction(function () use ($product) {
                $attrs = $this->parseAttributesFromName($product->name);

                // Buat Master Product baru berdasarkan data dari MarketplaceProduct
                $newMaster = MasterProduct::create([
                    'tenant_id'   => Auth::user()->tenant_id,
                    'sku'         => $product->marketplace_sku ?: ('SKU-' . time() . '-' . rand(100, 999)),
                    'name'        => $product->name,
                    'description' => $product->description,
                    'price'       => $product->price,
                    'stock'       => $product->stock,
                    'image_url'   => $product->image_url,
                    'is_active'   => true,
                    // Default values for standard dimensions/weight to prevent publish errors
                    'weight'      => 0.1, 
                    'length'      => 10,
                    'width'       => 10,
                    'height'      => 10,
                    'ukuran'      => $attrs['ukuran'],
                    'warna'       => $attrs['warna'],
                ]);

                // Update marketplace product agar tertaut ke master yang baru
                $product->update([
                    'master_product_id' => $newMaster->id,
                ]);

                return $newMaster;
            });

            return redirect()->route('products.publish', $master->id)
                ->with('success', "Master produk baru '{$master->name}' berhasil dibuat dari produk marketplace. Sekarang pilih toko tujuan untuk menduplikat.");

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses kloning produk: ' . $e->getMessage());
        }
    }

    public function unlink(MarketplaceProduct $product)
    {
        abort_unless($product->store->tenant_id === Auth::user()->tenant_id, 403);

        $product->update([
            'master_product_id' => null,
            'marketplace_sku' => null,
            'sync_stock' => false,
        ]);

        return back()->with('success', "Tautan produk marketplace '{$product->name}' berhasil dibatalkan.");
    }

    public function autoLink()
    {
        $tenantId = Auth::user()->tenant_id;

        // Ambil SEMUA produk marketplace milik tenant
        $marketplaceProducts = MarketplaceProduct::whereHas('store', function($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })->get();

        $linkedCount = 0;
        $unlinkedCount = 0;

        foreach ($marketplaceProducts as $product) {
            $skuClean = trim($product->marketplace_sku ?? '');

            if ($skuClean !== '') {
                $master = MasterProduct::where('tenant_id', $tenantId)
                    ->where('sku', $skuClean)
                    ->first();

                if ($master) {
                    if ($product->master_product_id !== $master->id) {
                        $product->update([
                            'master_product_id' => $master->id,
                            'sync_stock' => true,
                        ]);
                        $linkedCount++;
                    }
                } else {
                    if ($product->master_product_id !== null) {
                        $product->update([
                            'master_product_id' => null,
                        ]);
                        $unlinkedCount++;
                    }
                }
            } else {
                if ($product->master_product_id !== null) {
                    $product->update([
                        'master_product_id' => null,
                    ]);
                    $unlinkedCount++;
                }
            }
        }

        return back()->with('success', "Pembaruan Tautan Selesai. {$linkedCount} produk ditautkan ke Master Produk sesuai SKU terbaru, dan {$unlinkedCount} tautan lama yang SKU-nya sudah berubah/tidak ada dibersihkan.");
    }

    public function bulkPromote()
    {
        $tenantId = Auth::user()->tenant_id;

        // Ambil semua produk marketplace yang belum memiliki master_product_id
        $unlinkedProducts = MarketplaceProduct::whereNull('master_product_id')
            ->whereHas('store', function($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->get();

        $promotedCount = 0;

        DB::transaction(function () use ($unlinkedProducts, $tenantId, &$promotedCount) {
            foreach ($unlinkedProducts as $product) {
                // Refresh data untuk memastikan tidak terhubung oleh loop iterasi sebelumnya
                $product->refresh();
                if ($product->master_product_id) {
                    continue;
                }

                $skuClean = trim($product->marketplace_sku);

                // Buatkan SKU jika kosong
                if (empty($skuClean)) {
                    $skuClean = 'SKU-' . time() . '-' . rand(1000, 9999);
                }

                // Cek jika master dengan SKU ini sudah ada (karena iterasi sebelumnya)
                $existingMaster = MasterProduct::where('tenant_id', $tenantId)
                    ->where('sku', $skuClean)
                    ->first();

                if ($existingMaster) {
                    $product->update([
                        'master_product_id' => $existingMaster->id,
                        'sync_stock' => true,
                    ]);
                    continue;
                }

                // Buat Master Product baru
                $attrs = $this->parseAttributesFromName($product->name);
                $newMaster = MasterProduct::create([
                    'tenant_id'   => $tenantId,
                    'sku'         => $skuClean,
                    'name'        => $product->name,
                    'description' => $product->description,
                    'price'       => $product->price,
                    'stock'       => $product->stock,
                    'image_url'   => $product->image_url,
                    'is_active'   => true,
                    'weight'      => 0.1, 
                    'length'      => 10,
                    'width'       => 10,
                    'height'      => 10,
                    'ukuran'      => $attrs['ukuran'],
                    'warna'       => $attrs['warna'],
                ]);

                // Hubungkan produk marketplace
                $product->update([
                    'marketplace_sku' => $skuClean,
                    'master_product_id' => $newMaster->id,
                    'sync_stock' => true,
                ]);

                $promotedCount++;
            }
        });

        return back()->with('success', "Berhasil membuat {$promotedCount} Master Product baru secara massal dan menautkannya.");
    }

    /**
     * Parse ukuran and warna from variation name.
     */
    private function parseAttributesFromName(?string $name): array
    {
        $attributes = [
            'ukuran' => null,
            'warna'  => null,
        ];

        if (empty($name)) {
            return $attributes;
        }

        // Format variation names: "Product Name - Variant 1, Variant 2" or "Product Name - Variant 1"
        if (str_contains($name, ' - ')) {
            $parts = explode(' - ', $name);
            $variantPart = end($parts);

            // Split by comma, slash
            $options = preg_split('/[,\/]/', $variantPart);

            foreach ($options as $opt) {
                $opt = trim($opt);
                if (empty($opt)) {
                    continue;
                }

                // Match common size patterns (S, M, L, XL, Shoe Size, Waist Size, etc.)
                $isSize = preg_match('/^(s|m|l|xl|xxl|xxxl|2xl|3xl|4xl|5xl|all\s*size|one\s*size)$/i', $opt) ||
                          preg_match('/^\d+(\s*(cm|mm|m|gr|kg))?$/i', $opt) ||
                          preg_match('/^\d+\/\d+$/', $opt) ||
                          preg_match('/^(3[6-9]|4[0-6])$/', $opt); // Shoe sizes

                if ($isSize) {
                    $attributes['ukuran'] = $opt;
                } else {
                    $attributes['warna'] = $opt;
                }
            }
        }

        return $attributes;
    }

    public function printReport(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = $this->buildProductQuery($request);

        $products = $query->select('marketplace_products.*')->latest('marketplace_products.updated_at')->get();

        $totalCount = $products->count();
        $mappedCount = $products->whereNotNull('master_product_id')->count();
        $unmappedCount = $totalCount - $mappedCount;
        $totalStock = $products->sum('stock');
        $preorderCount = $products->filter(fn($p) => $p->isPreOrder())->count();
        $totalValue = $products->sum(function ($p) {
            return ($p->price ?? 0) * ($p->stock ?? 0);
        });

        $sinkronCount = $products->filter(function($p) {
            if (!$p->masterProduct || $p->isPreOrder()) return false;
            $expected = max(0, (int)$p->masterProduct->stock - (int)($p->safety_stock ?? 0));
            return (int)$p->stock === $expected;
        })->count();

        $bedaCount = $products->filter(function($p) {
            if (!$p->masterProduct || $p->isPreOrder()) return false;
            $expected = max(0, (int)$p->masterProduct->stock - (int)($p->safety_stock ?? 0));
            return (int)$p->stock !== $expected;
        })->count();

        $selectedChannel = null;
        if ($request->filled('channel_id')) {
            $selectedChannel = Channel::find($request->channel_id);
        }

        $selectedStore = null;
        if ($request->filled('store_id')) {
            $selectedStore = Store::find($request->store_id);
        }

        return view('marketplace_products.print_report', compact(
            'products',
            'totalCount',
            'mappedCount',
            'unmappedCount',
            'preorderCount',
            'sinkronCount',
            'bedaCount',
            'totalStock',
            'totalValue',
            'selectedChannel',
            'selectedStore'
        ));
    }
}
