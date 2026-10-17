<?php

namespace Tests\Feature\Stock;

use App\Enums\LocationType;
use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Livewire\StockOpnames\Form;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StockOpnameTest extends TestCase
{
    use RefreshDatabase;

    private Location $location;

    private User $gudang;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->location = Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);
        $this->gudang = User::factory()->create();
        $this->gudang->assignRole('Gudang');
    }

    private function makeProduct(string $code, string $name, string $category, float $qty, float $cost): Product
    {
        $cat = Category::firstOrCreate(['name' => $category]);
        $product = Product::create(['code' => $code, 'name' => $name, 'category_id' => $cat->id, 'type' => ProductType::Stock, 'base_unit' => 'Pcs', 'min_stock' => 5]);
        ProductUnit::create(['product_id' => $product->id, 'unit_name' => 'Pcs', 'conversion_factor' => 1, 'is_base' => true, 'price_sell' => 1000]);
        app(StockService::class)->applyIncomingCost($product, $this->location->id, $qty, $cost, StockMovementType::Opening, userId: $this->gudang->id);

        return $product;
    }

    public function test_opname_by_category_and_posting_uat_inv_04(): void
    {
        $aqua = $this->makeProduct('BRD-002', 'Aqua Botol 600ml', 'Minuman', 48, 3000);

        $component = Livewire::actingAs($this->gudang)
            ->test(Form::class)
            ->set('locationId', $this->location->id)
            ->set('scopeCategoryId', $aqua->category_id)
            ->call('createSession');

        $opnameId = $component->get('opnameId');
        $item = StockOpnameItem::where('stock_opname_id', $opnameId)->where('product_id', $aqua->id)->first();

        Livewire::actingAs($this->gudang)
            ->test(Form::class, ['stockOpname' => StockOpname::find($opnameId)])
            ->call('updateCount', $item->id, 45)
            ->call('post');

        $this->assertSame('45.000', $aqua->fresh()->stocks()->first()->qty);
        $this->assertSame(-3.0, (float) $item->fresh()->difference);
        $this->assertSame(-9000.0, (float) $item->fresh()->difference * (float) $aqua->fresh()->avg_cost);
    }

    public function test_draft_does_not_change_stock_and_blank_items_untouched_uat_inv_05(): void
    {
        $gula = $this->makeProduct('BRD-003', 'Gula Pasir Curah', 'Sembako', 25, 14500);
        $indomie = $this->makeProduct('BRD-001', 'Indomie Goreng Special', 'Makanan', 100, 2500);

        $component = Livewire::actingAs($this->gudang)
            ->test(Form::class)
            ->set('locationId', $this->location->id)
            ->call('createSession');

        $opnameId = $component->get('opnameId');
        $gulaItem = StockOpnameItem::where('stock_opname_id', $opnameId)->where('product_id', $gula->id)->first();

        $this->assertSame('25.000', $gula->fresh()->stocks()->first()->qty);

        Livewire::actingAs($this->gudang)
            ->test(Form::class, ['stockOpname' => StockOpname::find($opnameId)])
            ->call('updateCount', $gulaItem->id, 24)
            ->call('post');

        $this->assertSame('24.000', $gula->fresh()->stocks()->first()->qty);
        $this->assertSame('100.000', $indomie->fresh()->stocks()->first()->qty);
    }
}
