<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Spk;
use App\Models\SpkItem;
use App\Models\SpkItemExtra;
use App\Models\MasterProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpkHppCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_spk_creation_allocates_global_jasa_and_bahan_to_item_hpp()
    {
        $tenant = \App\Models\Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test.local',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
        ]);

        $product = MasterProduct::create([
            'tenant_id' => $tenant->id,
            'name' => 'Celana Chino L',
            'sku' => 'CHN-L',
            'price' => 150000,
            'cost_price' => 50000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('spks.store'), [
            'tanggal' => now()->format('Y-m-d'),
            'deadline' => now()->addDays(7)->format('Y-m-d'),
            'pemesan' => 'Bapak Budi',
            'items' => [
                [
                    'name' => 'Celana Chino L',
                    'sku' => 'CHN-L',
                    'size' => 'L',
                    'qty' => 100,
                ]
            ],
            'global_jasa' => [
                ['keterangan' => 'Jasa Jahit', 'nominal' => 150000],
                ['keterangan' => 'Jasa QC & Finishing', 'nominal' => 30000],
            ],
            'global_bahan' => [
                ['keterangan' => 'Benang & Kancing', 'nominal' => 20000],
            ],
        ]);

        $response->assertRedirect();

        $spk = Spk::first();
        $this->assertNotNull($spk);

        $item = SpkItem::where('spk_id', $spk->id)->first();
        $this->assertNotNull($item);
        $this->assertEquals(100, $item->quantity);

        // Total Global Costs = 150.000 + 30.000 + 20.000 = 200.000
        // HPP per unit = 200.000 / 100 = 2.000
        $this->assertEquals(2000, $item->hpp);

        $extras = SpkItemExtra::where('spk_item_id', $item->id)->get();
        $this->assertCount(3, $extras);
    }

    public function test_spk_update_calculates_hpp_from_spk_level_bahan_and_costs()
    {
        $tenant = \App\Models\Tenant::create([
            'name' => 'Test Tenant 2',
            'domain' => 'test2.local',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
        ]);

        $spk = Spk::create([
            'tenant_id' => $tenant->id,
            'no_spk' => 'SPK-2026-001',
            'tanggal' => now()->format('Y-m-d'),
            'deadline' => now()->addDays(7)->format('Y-m-d'),
            'pemesan' => 'Juragan Kaos',
            'tahap_saat_ini' => 'potong',
        ]);

        $itemA = SpkItem::create([
            'spk_id' => $spk->id,
            'nama_produk' => 'Kaos Polo L',
            'sku' => 'POLO-L',
            'ukuran' => 'L',
            'quantity' => 60,
            'hpp' => 0,
        ]);

        $itemB = SpkItem::create([
            'spk_id' => $spk->id,
            'nama_produk' => 'Kaos Polo XL',
            'sku' => 'POLO-XL',
            'ukuran' => 'XL',
            'quantity' => 40,
            'hpp' => 0,
        ]);

        $response = $this->actingAs($user)->put(route('spks.update', $spk), [
            'tanggal' => now()->format('Y-m-d'),
            'deadline' => now()->addDays(7)->format('Y-m-d'),
            'pemesan' => 'Juragan Kaos',
            'tahap_saat_ini' => 'potong',
            'rincian' => [
                0 => [
                    'produk' => [
                        0 => [
                            'sku_produk' => 'POLO-L',
                            'nama_produk' => 'Kaos Polo L',
                            'ukuran' => 'L',
                            'qty_produksi' => 60,
                        ],
                        1 => [
                            'sku_produk' => 'POLO-XL',
                            'nama_produk' => 'Kaos Polo XL',
                            'ukuran' => 'XL',
                            'qty_produksi' => 40,
                        ],
                    ],
                ],
            ],
            'spk_bahan' => [
                0 => [
                    'nama_bahan' => 'BB-TH',
                    'qty_bahan' => '2',
                    'satuan' => 'Roll',
                    'harga' => '500.000',
                    'subtotal' => '1.000.000',
                ],
            ],
            'spk_biaya_produksi' => '300.000',
            'spk_biaya_tambahan' => '100.000',
            'spk_ket_tambahan' => 'Hangtag & OPP',
        ]);

        $response->assertRedirect();

        $freshItemA = $itemA->fresh();
        $freshItemB = $itemB->fresh();

        // Grand Total Cost = 1.000.000 (bahan) + 300.000 (produksi) + 100.000 (tambahan) = 1.400.000
        // Total Qty = 100 pcs -> HPP = 14.000 / pcs
        $this->assertEquals(14000, $freshItemA->hpp);
        $this->assertEquals(14000, $freshItemB->hpp);

        // Check Extras for Item A (60% ratio)
        $extrasA = SpkItemExtra::where('spk_item_id', $itemA->id)->get();
        $this->assertCount(3, $extrasA);
        $this->assertEquals(600000, $extrasA->where('nominal', 600000)->first()->nominal);
        $this->assertEquals(180000, $extrasA->where('nominal', 180000)->first()->nominal);
        $this->assertEquals(60000, $extrasA->where('nominal', 60000)->first()->nominal);
        $this->assertEquals(840000, $extrasA->sum('nominal'));

        // Check Extras for Item B (40% ratio)
        $extrasB = SpkItemExtra::where('spk_item_id', $itemB->id)->get();
        $this->assertCount(3, $extrasB);
        $this->assertEquals(400000, $extrasB->where('nominal', 400000)->first()->nominal);
        $this->assertEquals(120000, $extrasB->where('nominal', 120000)->first()->nominal);
        $this->assertEquals(40000, $extrasB->where('nominal', 40000)->first()->nominal);
        $this->assertEquals(560000, $extrasB->sum('nominal'));
    }
}
