<?php

namespace Tests\Feature;

use App\Models\FaqCategory;
use App\Models\FaqItem;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'   => 'Faq Test Tenant',
            'status' => 'active',
        ]);

        $this->adminUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Admin User',
            'email'     => 'admin@faqtest.com',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
        ]);

        $this->regularUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Regular User',
            'email'     => 'user@faqtest.com',
            'password'  => bcrypt('password'),
            'role'      => 'staff',
        ]);
    }

    public function test_faq_index_is_accessible_for_all_logged_in_users(): void
    {
        $this->actingAs($this->regularUser);

        $response = $this->get(route('faq.index'));

        $response->assertStatus(200);
    }

    public function test_faq_management_requires_admin(): void
    {
        $this->actingAs($this->regularUser);

        $response = $this->get(route('faq.manage'));

        $response->assertStatus(403);
    }

    public function test_faq_management_accessible_by_admin(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('faq.manage'));

        $response->assertStatus(200);
        $response->assertSee('Kelola Panduan & FAQ');
    }

    public function test_faq_category_crud_by_admin(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Create
        $response = $this->post(route('faq.categories.store'), [
            'name'           => 'Category CRUD Test',
            'subtitle'       => 'Test Subtitle',
            'icon'           => 'fas fa-vial',
            'color'          => '#8B5CF6',
            'color_rgb'      => '139, 92, 246',
            'read_time'      => '2 mnt',
            'workflow_title' => 'Langkah Pengujian',
            'sort_order'     => 10,
        ]);

        $response->assertRedirect(route('faq.manage'));
        $this->assertDatabaseHas('faq_categories', [
            'name' => 'Category CRUD Test',
            'slug' => 'category-crud-test',
        ]);

        $category = FaqCategory::where('slug', 'category-crud-test')->firstOrFail();

        // 2. Update
        $response = $this->put(route('faq.categories.update', $category), [
            'name'           => 'Updated Category CRUD Test',
            'subtitle'       => 'Updated Subtitle',
            'icon'           => 'fas fa-check',
            'color'          => '#10B981',
            'color_rgb'      => '16, 185, 129',
            'read_time'      => '3 mnt',
            'workflow_title' => 'Updated Langkah',
            'sort_order'     => 20,
        ]);

        $response->assertRedirect(route('faq.manage'));
        $this->assertDatabaseHas('faq_categories', [
            'id'   => $category->id,
            'name' => 'Updated Category CRUD Test',
            'slug' => 'updated-category-crud-test',
        ]);

        // 3. Delete
        $response = $this->delete(route('faq.categories.destroy', $category));

        $response->assertRedirect(route('faq.manage'));
        $this->assertDatabaseMissing('faq_categories', [
            'id' => $category->id,
        ]);
    }

    public function test_faq_item_crud_by_admin(): void
    {
        $this->actingAs($this->adminUser);

        $category = FaqCategory::create([
            'name'           => 'Item CRUD Test',
            'slug'           => 'item-crud-test',
            'icon'           => 'fas fa-vial',
            'color'          => '#8B5CF6',
            'color_rgb'      => '139, 92, 246',
            'workflow_title' => 'Alur',
            'sort_order'     => 1,
        ]);

        // 1. Create Workflow
        $response = $this->post(route('faq.items.store'), [
            'faq_category_id' => $category->id,
            'type'            => 'workflow',
            'title'           => 'Langkah Uji',
            'content'         => 'Konten langkah uji',
            'sort_order'      => 1,
        ]);

        $response->assertRedirect(route('faq.manage'));
        $this->assertDatabaseHas('faq_items', [
            'faq_category_id' => $category->id,
            'type'            => 'workflow',
            'title'           => 'Langkah Uji',
        ]);

        $item = FaqItem::where('title', 'Langkah Uji')->firstOrFail();

        // 2. Update Item
        $response = $this->put(route('faq.items.update', $item), [
            'faq_category_id' => $category->id,
            'type'            => 'faq',
            'title'           => 'Pertanyaan Uji',
            'content'         => 'Jawaban pertanyaan uji',
            'sort_order'      => 2,
        ]);

        $response->assertRedirect(route('faq.manage'));
        $this->assertDatabaseHas('faq_items', [
            'id'    => $item->id,
            'type'  => 'faq',
            'title' => 'Pertanyaan Uji',
        ]);

        // 3. Delete Item
        $response = $this->delete(route('faq.items.destroy', $item));

        $response->assertRedirect(route('faq.manage'));
        $this->assertDatabaseMissing('faq_items', [
            'id' => $item->id,
        ]);
    }
}
