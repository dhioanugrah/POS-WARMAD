<?php

namespace Tests\Feature\Products;

use App\Livewire\Products\Index;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\SaleItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductIndexTest extends TestCase
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

    private function makeProduct(): Product
    {
        $product = Product::create(['code' => 'BRD-004', 'name' => 'Sabun Mandi 85g', 'type' => 'stock', 'base_unit' => 'Pcs', 'min_stock' => 10]);
        ProductUnit::create(['product_id' => $product->id, 'unit_name' => 'Pcs', 'conversion_factor' => 1, 'is_base' => true, 'price_sell' => 4500]);

        return $product;
    }

    public function test_can_deactivate_product_uat_mst_06(): void
    {
        $product = $this->makeProduct();

        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->call('toggleActive', $product->id);

        $this->assertFalse($product->fresh()->is_active);
    }

    public function test_cannot_permanently_delete_product_with_transactions_uat_mst_07(): void
    {
        $product = $this->makeProduct();
        SaleItem::factory()->create(['product_id' => $product->id]);

        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->call('delete', $product->id);

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_can_permanently_delete_product_without_transactions(): void
    {
        $product = $this->makeProduct();

        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->call('delete', $product->id);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }
}
