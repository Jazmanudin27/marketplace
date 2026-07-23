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
}
