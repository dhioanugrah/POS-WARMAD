<?php

namespace Tests\Feature\Services;

use App\Enums\LocationType;
use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userId = User::factory()->create()->id;
    }

    private function makeLocation(): Location
    {
        return Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);
    }

    private function makeProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'code' => 'BRD-001',
            'name' => 'Indomie Goreng Special',
            'type' => ProductType::Stock,
            'base_unit' => 'Pcs',
            'min_stock' => 40,
        ], $overrides));
    }

    public function test_move_increases_and_records_movement(): void
    {
        $location = $this->makeLocation();
        $product = $this->makeProduct();

        app(StockService::class)->move($product, $location->id, 100, StockMovementType::Opening, unitCost: 2500, userId: $this->userId);

        $this->assertSame('100.000', $product->stocks()->first()->qty);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'qty_change' => 100,
            'qty_after' => 100,
        ]);
    }

    public function test_move_ignores_service_products(): void
    {
        $location = $this->makeLocation();
        $product = $this->makeProduct(['type' => ProductType::Service, 'code' => 'JSA-001']);

        $movement = app(StockService::class)->move($product, $location->id, -1, StockMovementType::Sale, userId: $this->userId);

        $this->assertNull($movement);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('product_stocks', 0);
    }

    public function test_move_blocks_negative_stock_when_disallowed(): void
    {
        setting_set('pos.allow_negative_stock', false);
        $location = $this->makeLocation();
        $product = $this->makeProduct(['code' => 'BRD-004', 'name' => 'Sabun Mandi 85g', 'min_stock' => 10]);

        app(StockService::class)->move($product, $location->id, 5, StockMovementType::Opening, unitCost: 3600, userId: $this->userId);

        $this->expectException(InsufficientStockException::class);
        $this->expectExceptionMessage('Stok Sabun Mandi 85g tidak cukup. Tersedia 5');

        app(StockService::class)->move($product, $location->id, -6, StockMovementType::Sale, userId: $this->userId);
    }

    public function test_move_allows_negative_stock_when_enabled(): void
    {
        setting_set('pos.allow_negative_stock', true);
        $location = $this->makeLocation();
        $product = $this->makeProduct(['code' => 'BRD-004', 'min_stock' => 10]);

        app(StockService::class)->move($product, $location->id, 5, StockMovementType::Opening, unitCost: 3600, userId: $this->userId);
        app(StockService::class)->move($product, $location->id, -6, StockMovementType::Sale, userId: $this->userId);

        $this->assertSame('-1.000', $product->stocks()->first()->qty);
    }

    public function test_apply_incoming_cost_computes_weighted_average(): void
    {
        $location = $this->makeLocation();
        $product = $this->makeProduct();
        $service = app(StockService::class);

        $service->applyIncomingCost($product, $location->id, 100, 2500, StockMovementType::Opening, userId: $this->userId);
        $service->applyIncomingCost($product->fresh(), $location->id, 80, 2600, StockMovementType::Purchase, userId: $this->userId);

        $product->refresh();

        $this->assertSame('2544.44', (string) $product->avg_cost);
        $this->assertSame('180.000', $product->stocks()->first()->qty);
    }

    public function test_apply_incoming_cost_sets_avg_directly_when_starting_from_zero(): void
    {
        $location = $this->makeLocation();
        $product = $this->makeProduct();

        app(StockService::class)->applyIncomingCost($product, $location->id, 24, 90000 / 24, StockMovementType::Opening, userId: $this->userId);

        $this->assertSame('3750.00', (string) $product->fresh()->avg_cost);
    }
}
