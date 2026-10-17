<?php

namespace Tests\Feature\Products;

use App\Livewire\Products\Categories;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoriesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        return $user;
    }

    public function test_can_create_category_uat_mst_01(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Categories::class)
            ->call('create')
            ->set('name', 'Rokok')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', ['name' => 'Rokok']);
    }

    public function test_cannot_delete_category_in_use_uat_mst_02(): void
    {
        $category = Category::create(['name' => 'Makanan']);
        Product::create(['code' => 'BRD-001', 'name' => 'Indomie', 'category_id' => $category->id, 'type' => 'stock', 'base_unit' => 'Pcs']);

        Livewire::actingAs($this->admin())
            ->test(Categories::class)
            ->call('delete', $category->id);

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_can_manage_brands(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Categories::class)
            ->set('activeTab', 'merek')
            ->call('create')
            ->set('name', 'Indofood')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('brands', ['name' => 'Indofood']);
    }

    public function test_kasir_cannot_access_categories_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Kasir');

        $this->actingAs($user)->get('/categories')->assertForbidden();
    }
}
