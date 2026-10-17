<?php

namespace Tests\Feature\Services;

use App\Enums\LocationType;
use App\Enums\PaymentStatus;
use App\Enums\ProductType;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\PosException;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\User;
use App\Services\Sales\CheckoutData;
use App\Services\Sales\CheckoutItemData;
use App\Services\Sales\CheckoutPaymentData;
use App\Services\SaleService;
use App\Services\StockService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleServiceTest extends TestCase
{
    use RefreshDatabase;

    private Location $location;

    private User $kasir;

    private User $admin;

    private ProductUnit $indomiePcs;

    private ProductUnit $indomieDus;

    private Product $indomie;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, SettingsSeeder::class]);

        $this->location = Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);

        $this->kasir = User::factory()->create();
        $this->kasir->assignRole('Kasir');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Admin');

        $this->indomie = Product::create(['code' => 'BRD-001', 'name' => 'Indomie Goreng Special', 'type' => ProductType::Stock, 'base_unit' => 'Pcs', 'min_stock' => 40]);
        $this->indomiePcs = ProductUnit::create(['product_id' => $this->indomie->id, 'unit_name' => 'Pcs', 'conversion_factor' => 1, 'is_base' => true, 'barcode' => '8998866200301', 'price_sell' => 3000, 'is_default_sale' => true]);
        $this->indomieDus = ProductUnit::create(['product_id' => $this->indomie->id, 'unit_name' => 'Dus', 'conversion_factor' => 40, 'barcode' => '18998866200308', 'price_sell' => 115000]);

        app(StockService::class)->applyIncomingCost($this->indomie, $this->location->id, 100, 2500, StockMovementType::Opening, userId: $this->admin->id);
    }

    private function service(): SaleService
    {
        return app(SaleService::class);
    }

    public function test_cash_payment_uat_pos_11(): void
    {
        $sale = $this->service()->checkout(new CheckoutData(
            locationId: $this->location->id,
            userId: $this->kasir->id,
            shiftId: null,
            customerId: null,
            items: [new CheckoutItemData($this->indomiePcs->id, 3)],
            payments: [new CheckoutPaymentData('cash', 10000)],
        ));

        $this->assertSame(9000.0, (float) $sale->total);
        $this->assertSame(1000.0, (float) $sale->change_amount);
        $this->assertSame(9000.0, (float) $sale->paid_amount);
        $this->assertStringStartsWith('PJ-TKO-', $sale->number);
        $this->assertSame('97.000', $this->indomie->fresh()->stocks()->first()->qty);
    }

    public function test_underpaid_cash_sale_to_umum_is_rejected_uat_pos_12(): void
    {
        $this->expectException(PosException::class);
        $this->expectExceptionMessage('Pembayaran kurang 4.000');

        $this->service()->checkout(new CheckoutData(
            locationId: $this->location->id,
            userId: $this->kasir->id,
            shiftId: null,
            customerId: null,
            items: [new CheckoutItemData($this->indomiePcs->id, 3)],
            payments: [new CheckoutPaymentData('cash', 5000)],
        ));

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_split_payment_uat_pos_13(): void
    {
        $sale = $this->service()->checkout(new CheckoutData(
            locationId: $this->location->id,
            userId: $this->kasir->id,
            shiftId: null,
            customerId: null,
            items: [new CheckoutItemData($this->indomieDus->id, 1)],
            payments: [new CheckoutPaymentData('cash', 50000), new CheckoutPaymentData('qris', 65000)],
        ));

        $this->assertSame(115000.0, (float) $sale->total);
        $this->assertSame(0.0, (float) $sale->change_amount);
        $this->assertSame(PaymentStatus::Paid, $sale->payment_status);
        $this->assertCount(2, $sale->payments);
    }

    public function test_non_cash_overpayment_is_rejected_uat_pos_14(): void
    {
        $this->expectException(PosException::class);
        $this->expectExceptionMessage('Pembayaran non-tunai tidak boleh melebihi sisa tagihan.');

        $this->service()->checkout(new CheckoutData(
            locationId: $this->location->id,
            userId: $this->kasir->id,
            shiftId: null,
            customerId: null,
            items: [new CheckoutItemData($this->indomiePcs->id, 3)],
            payments: [new CheckoutPaymentData('qris', 20000)],
        ));
    }

    public function test_item_discount_percent_uat_pos_08(): void
    {
        $sale = $this->service()->checkout(new CheckoutData(
            locationId: $this->location->id,
            userId: $this->admin->id,
            shiftId: null,
            customerId: null,
            items: [new CheckoutItemData($this->indomiePcs->id, 3, discountType: 'percent', discountValue: 10)],
            payments: [new CheckoutPaymentData('cash', 8100)],
        ));

        $this->assertSame(900.0, (float) $sale->items->first()->discount_amount);
        $this->assertSame(8100.0, (float) $sale->subtotal);
    }

    public function test_total_discount_amount_uat_pos_09(): void
    {
        $aqua = Product::create(['code' => 'BRD-002', 'name' => 'Aqua Botol 600ml', 'type' => ProductType::Stock, 'base_unit' => 'Botol', 'min_stock' => 24]);
        $karton = ProductUnit::create(['product_id' => $aqua->id, 'unit_name' => 'Karton', 'conversion_factor' => 24, 'is_base' => true, 'price_sell' => 90000]);
        app(StockService::class)->applyIncomingCost($aqua, $this->location->id, 24, 3000, StockMovementType::Opening, userId: $this->admin->id);

        $gula = Product::create(['code' => 'BRD-003', 'name' => 'Gula Pasir Curah', 'type' => ProductType::Stock, 'base_unit' => 'Kg', 'min_stock' => 10]);
        $kg = ProductUnit::create(['product_id' => $gula->id, 'unit_name' => 'Kg', 'conversion_factor' => 1, 'is_base' => true, 'price_sell' => 17000]);
        app(StockService::class)->applyIncomingCost($gula, $this->location->id, 25, 14500, StockMovementType::Opening, userId: $this->admin->id);

        $sale = $this->service()->checkout(new CheckoutData(
            locationId: $this->location->id,
            userId: $this->admin->id,
            shiftId: null,
            customerId: null,
            items: [new CheckoutItemData($karton->id, 1), new CheckoutItemData($kg->id, 1)],
            payments: [new CheckoutPaymentData('cash', 100000)],
            discountType: 'amount',
            discountValue: 7000,
        ));

        $this->assertSame(107000.0, (float) $sale->subtotal);
        $this->assertSame(7000.0, (float) $sale->discount_amount);
        $this->assertSame(100000.0, (float) $sale->total);
    }

    public function test_rounding_to_100_uat_set_03(): void
    {
        setting_set('pos.rounding', 100);
        $gula = Product::create(['code' => 'BRD-003', 'name' => 'Gula Pasir Curah', 'type' => ProductType::Stock, 'base_unit' => 'Kg', 'min_stock' => 10]);
        $kg = ProductUnit::create(['product_id' => $gula->id, 'unit_name' => 'Kg', 'conversion_factor' => 1, 'is_base' => true, 'price_sell' => 17000]);
        app(StockService::class)->applyIncomingCost($gula, $this->location->id, 25, 14500, StockMovementType::Opening, userId: $this->admin->id);

        $sale = $this->service()->checkout(new CheckoutData(
            locationId: $this->location->id,
            userId: $this->admin->id,
            shiftId: null,
            customerId: null,
            items: [new CheckoutItemData($kg->id, 0.726)],
            payments: [new CheckoutPaymentData('cash', 12300)],
        ));

        $this->assertSame(12342.0, (float) $sale->subtotal);
        $this->assertSame(-42.0, (float) $sale->rounding_amount);
        $this->assertSame(12300.0, (float) $sale->total);
    }

    public function test_negative_stock_blocked_when_disallowed_uat_set_05(): void
    {
        setting_set('pos.allow_negative_stock', false);
        $sabun = Product::create(['code' => 'BRD-004', 'name' => 'Sabun Mandi 85g', 'type' => ProductType::Stock, 'base_unit' => 'Pcs', 'min_stock' => 10]);
        $pcs = ProductUnit::create(['product_id' => $sabun->id, 'unit_name' => 'Pcs', 'conversion_factor' => 1, 'is_base' => true, 'price_sell' => 4500]);
        app(StockService::class)->applyIncomingCost($sabun, $this->location->id, 5, 3600, StockMovementType::Opening, userId: $this->admin->id);

        $this->expectException(InsufficientStockException::class);
        $this->expectExceptionMessage('Stok Sabun Mandi 85g tidak cukup. Tersedia 5');

        $this->service()->checkout(new CheckoutData(
            locationId: $this->location->id,
            userId: $this->admin->id,
            shiftId: null,
            customerId: null,
            items: [new CheckoutItemData($pcs->id, 6)],
            payments: [new CheckoutPaymentData('cash', 27000)],
        ));

        $this->assertSame('5.000', $sabun->fresh()->stocks()->first()->qty);
    }

    public function test_client_price_override_is_ignored_without_permission_uat_pos_20(): void
    {
        $sale = $this->service()->checkout(new CheckoutData(
            locationId: $this->location->id,
            userId: $this->kasir->id,
            shiftId: null,
            customerId: null,
            items: [new CheckoutItemData($this->indomiePcs->id, 1, priceOverride: 1)],
            payments: [new CheckoutPaymentData('cash', 3000)],
        ));

        $this->assertSame(3000.0, (float) $sale->items->first()->price);
        $this->assertFalse($sale->items->first()->price_overridden);
    }

    public function test_price_override_is_honored_with_permission(): void
    {
        $sale = $this->service()->checkout(new CheckoutData(
            locationId: $this->location->id,
            userId: $this->admin->id,
            shiftId: null,
            customerId: null,
            items: [new CheckoutItemData($this->indomiePcs->id, 1, priceOverride: 2500)],
            payments: [new CheckoutPaymentData('cash', 2500)],
        ));

        $this->assertSame(2500.0, (float) $sale->items->first()->price);
        $this->assertTrue($sale->items->first()->price_overridden);
    }

    public function test_kasir_discount_beyond_cap_is_rejected(): void
    {
        $this->expectException(PosException::class);

        $this->service()->checkout(new CheckoutData(
            locationId: $this->location->id,
            userId: $this->kasir->id,
            shiftId: null,
            customerId: null,
            items: [new CheckoutItemData($this->indomiePcs->id, 10)],
            payments: [new CheckoutPaymentData('cash', 100000)],
            discountType: 'percent',
            discountValue: 15,
        ));
    }

    public function test_credit_sale_uat_pos_26(): void
    {
        $customer = Customer::create(['code' => 'PLG-0001', 'name' => 'Bu Sari', 'credit_limit' => 500000, 'is_active' => true]);

        $sale = $this->service()->checkout(new CheckoutData(
            locationId: $this->location->id,
            userId: $this->admin->id,
            shiftId: null,
            customerId: $customer->id,
            items: [new CheckoutItemData($this->indomieDus->id, 1)],
            payments: [new CheckoutPaymentData('cash', 15000)],
        ));

        $this->assertSame(PaymentStatus::Partial, $sale->payment_status);
        $this->assertSame(100000.0, (float) $sale->total - (float) $sale->paid_amount);
        $this->assertNotNull($sale->due_date);
    }

    public function test_credit_rejected_for_customer_without_credit_facility_uat_pos_27a(): void
    {
        $customer = Customer::create(['code' => 'PLG-0002', 'name' => 'Pak Joko', 'credit_limit' => 0, 'is_active' => true]);

        $this->expectException(PosException::class);
        $this->expectExceptionMessage('Pelanggan tidak memiliki fasilitas kredit.');

        $this->service()->checkout(new CheckoutData(
            locationId: $this->location->id,
            userId: $this->admin->id,
            shiftId: null,
            customerId: $customer->id,
            items: [new CheckoutItemData($this->indomiePcs->id, 1)],
            payments: [new CheckoutPaymentData('cash', 1000)],
        ));
    }

    public function test_credit_rejected_when_exceeding_limit_uat_pos_27b(): void
    {
        $customer = Customer::create(['code' => 'PLG-0001', 'name' => 'Bu Sari', 'credit_limit' => 500000, 'is_active' => true]);

        // Existing outstanding receivable of 100.000 (from a prior partial sale).
        $this->service()->checkout(new CheckoutData(
            locationId: $this->location->id,
            userId: $this->admin->id,
            shiftId: null,
            customerId: $customer->id,
            items: [new CheckoutItemData($this->indomieDus->id, 1)],
            payments: [new CheckoutPaymentData('cash', 15000)],
        ));

        $karton = ProductUnit::create(['product_id' => Product::create(['code' => 'X', 'name' => 'X', 'type' => ProductType::Stock, 'base_unit' => 'Pcs'])->id, 'unit_name' => 'Pcs', 'conversion_factor' => 1, 'is_base' => true, 'price_sell' => 450000]);

        $this->expectException(PosException::class);
        $this->expectExceptionMessage('Melebihi batas kredit (sisa 400.000)');

        $this->service()->checkout(new CheckoutData(
            locationId: $this->location->id,
            userId: $this->admin->id,
            shiftId: null,
            customerId: $customer->id,
            items: [new CheckoutItemData($karton->id, 1)],
            payments: [],
        ));
    }

    public function test_void_reverses_stock_and_excludes_from_completed_status_uat_pos_22(): void
    {
        $sale = $this->service()->checkout(new CheckoutData(
            locationId: $this->location->id,
            userId: $this->kasir->id,
            shiftId: null,
            customerId: null,
            items: [new CheckoutItemData($this->indomiePcs->id, 3)],
            payments: [new CheckoutPaymentData('cash', 10000)],
        ));

        $this->assertSame('97.000', $this->indomie->fresh()->stocks()->first()->qty);

        $this->service()->void($sale, 'Salah input', $this->admin);

        $this->assertSame(SaleStatus::Void, $sale->fresh()->status);
        $this->assertSame('100.000', $this->indomie->fresh()->stocks()->first()->qty);
        $this->assertSame($this->admin->id, $sale->fresh()->voided_by);
        $this->assertNotNull($sale->fresh()->payments->first()->voided_at);
    }
}
