<?php

namespace Tests\Feature;

use App\Models\MasterProduct;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected Supplier $supplier;
    protected MasterProduct $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

        $this->tenant = Tenant::create([
            'name' => 'PO Tenant',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Purchasing Manager',
            'email' => 'purchasing@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->supplier = Supplier::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Supplier Utama',
            'phone' => '081234567890',
            'address' => 'Jalan Supplier No 1',
            'is_active' => true,
        ]);

        $this->product = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'SKU-PO-01',
            'name' => 'Produk PO',
            'price' => 15000,
            'cost_price' => 8000,
            'stock' => 10,
            'is_active' => true,
        ]);
    }

    public function test_purchasing_manager_can_create_purchase_order_draft(): void
    {
        $payload = [
            'supplier_id' => $this->supplier->id,
            'po_date' => now()->format('Y-m-d'),
            'notes' => 'Catatan PO Penting',
            'items' => [
                [
                    'master_product_id' => $this->product->id,
                    'quantity' => 20,
                    'unit_price' => 7500,
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->post(route('purchase_orders.store'), $payload);

        $response->assertRedirect(route('purchase_orders.index'));
        $this->assertDatabaseHas('purchase_orders', [
            'tenant_id' => $this->tenant->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'draft',
            'total_amount' => 150000,
        ]);
    }

    public function test_can_release_purchase_order_to_ordered(): void
    {
        $po = PurchaseOrder::create([
            'tenant_id' => $this->tenant->id,
            'supplier_id' => $this->supplier->id,
            'po_number' => 'PO-TEST-001',
            'po_date' => now(),
            'status' => 'draft',
            'total_amount' => 10000,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('purchase_orders.update_status', $po), [
                'status' => 'ordered'
            ]);

        $response->assertRedirect(route('purchase_orders.show', $po));
        $po->refresh();
        $this->assertEquals('ordered', $po->status);
    }

    public function test_incoming_goods_can_be_received_from_active_po(): void
    {
        $po = PurchaseOrder::create([
            'tenant_id' => $this->tenant->id,
            'supplier_id' => $this->supplier->id,
            'po_number' => 'PO-TEST-002',
            'po_date' => now(),
            'status' => 'ordered',
            'total_amount' => 160000,
        ]);

        $poItem = $po->items()->create([
            'master_product_id' => $this->product->id,
            'quantity' => 20,
            'unit_price' => 8000,
            'received_quantity' => 0,
        ]);

        $incomingPayload = [
            'source_type' => 'supplier',
            'supplier_id' => $this->supplier->id,
            'purchase_order_id' => $po->id,
            'incoming_date' => now()->format('Y-m-d\TH:i'),
            'reference' => 'PO-TEST-002',
            'products' => [$this->product->id],
            'quantities' => [20],
            'cost_prices' => [8000],
        ];

        $response = $this->actingAs($this->user)
            ->post(route('incoming_goods.store'), $incomingPayload);

        $response->assertRedirect(route('incoming_goods.index'));

        // Check PO status is updated to received
        $po->refresh();
        $this->assertEquals('received', $po->status);

        $poItem->refresh();
        $this->assertEquals(20, $poItem->received_quantity);

        // Check local master stock increased from 10 to 30
        $this->product->refresh();
        $this->assertEquals(30, $this->product->stock);

        // Check stock movement PO ID is set
        $this->assertDatabaseHas('stock_movements', [
            'tenant_id' => $this->tenant->id,
            'master_product_id' => $this->product->id,
            'purchase_order_id' => $po->id,
            'quantity' => 20,
        ]);
    }
}
