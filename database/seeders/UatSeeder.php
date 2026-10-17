<?php

namespace Database\Seeders;

use App\Enums\LocationType;
use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Supplier;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Test data fixture matching docs/05-UAT.md §2 exactly, so every UAT
 * scenario can be run against a fresh `migrate:fresh --seed`.
 */
class UatSeeder extends Seeder
{
    public function run(): void
    {
        $stockService = app(StockService::class);

        $toko = Location::query()->firstOrCreate(
            ['code' => 'TKO'],
            ['name' => 'Toko Utama', 'type' => LocationType::Store, 'is_active' => true]
        );

        Location::query()->firstOrCreate(
            ['code' => 'GDG'],
            ['name' => 'Gudang', 'type' => LocationType::Warehouse, 'is_active' => true]
        );

        $users = [
            ['email' => 'owner@pos.com', 'name' => 'Owner', 'role' => 'Owner', 'pin' => '123456'],
            ['email' => 'admin@pos.com', 'name' => 'Admin', 'role' => 'Admin', 'pin' => '654321'],
            ['email' => 'kasir@pos.com', 'name' => 'Kasir', 'role' => 'Kasir', 'pin' => null],
            ['email' => 'kasir2@pos.com', 'name' => 'Kasir Dua', 'role' => 'Kasir', 'pin' => null],
            ['email' => 'gudang@pos.com', 'name' => 'Gudang', 'role' => 'Gudang', 'pin' => null],
            ['email' => 'nonaktif@pos.com', 'name' => 'Nonaktif', 'role' => 'Kasir', 'pin' => null, 'is_active' => false],
        ];

        foreach ($users as $data) {
            $user = User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'supervisor_pin' => $data['pin'],
                    'is_active' => $data['is_active'] ?? true,
                    'default_location_id' => $toko->id,
                    'email_verified_at' => now(),
                ]
            );
            $user->syncRoles([$data['role']]);

            if ($data['email'] === 'owner@pos.com') {
                $ownerId = $user->id;
            }
        }

        $categories = [
            'Makanan' => 'Makanan',
            'Minuman' => 'Minuman',
            'Sembako' => 'Sembako',
            'Kebutuhan Rumah' => 'Kebutuhan Rumah',
            'Jasa' => 'Jasa',
        ];
        $categoryIds = [];
        foreach ($categories as $key => $name) {
            $categoryIds[$key] = Category::query()->firstOrCreate(['name' => $name])->id;
        }

        $products = [
            [
                'code' => 'BRD-001', 'name' => 'Indomie Goreng Special', 'category' => 'Makanan',
                'base_unit' => 'Pcs', 'min_stock' => 40,
                'units' => [
                    ['unit_name' => 'Pcs', 'conversion_factor' => 1, 'barcode' => '8998866200301', 'price_sell' => 3000, 'is_base' => true, 'is_default_sale' => true],
                    ['unit_name' => 'Dus', 'conversion_factor' => 40, 'barcode' => '18998866200308', 'price_sell' => 115000, 'is_base' => false, 'is_default_sale' => false],
                ],
                'opening_qty' => 100, 'opening_cost' => 2500,
            ],
            [
                'code' => 'BRD-002', 'name' => 'Aqua Botol 600ml', 'category' => 'Minuman',
                'base_unit' => 'Botol', 'min_stock' => 24,
                'units' => [
                    ['unit_name' => 'Botol', 'conversion_factor' => 1, 'barcode' => '8886008101053', 'price_sell' => 4000, 'is_base' => true, 'is_default_sale' => true],
                    ['unit_name' => 'Karton', 'conversion_factor' => 24, 'barcode' => '18886008101050', 'price_sell' => 90000, 'is_base' => false, 'is_default_sale' => false],
                ],
                'opening_qty' => 48, 'opening_cost' => 3000,
            ],
            [
                'code' => 'BRD-003', 'name' => 'Gula Pasir Curah', 'category' => 'Sembako',
                'base_unit' => 'Kg', 'min_stock' => 10,
                'units' => [
                    ['unit_name' => 'Kg', 'conversion_factor' => 1, 'barcode' => null, 'price_sell' => 17000, 'is_base' => true, 'is_default_sale' => true],
                ],
                'opening_qty' => 25, 'opening_cost' => 14500,
            ],
            [
                'code' => 'BRD-004', 'name' => 'Sabun Mandi 85g', 'category' => 'Kebutuhan Rumah',
                'base_unit' => 'Pcs', 'min_stock' => 10,
                'units' => [
                    ['unit_name' => 'Pcs', 'conversion_factor' => 1, 'barcode' => '8999999003538', 'price_sell' => 4500, 'is_base' => true, 'is_default_sale' => true],
                ],
                'opening_qty' => 5, 'opening_cost' => 3600,
            ],
            [
                'code' => 'JSA-001', 'name' => 'Jasa Bungkus Kado', 'category' => 'Jasa',
                'base_unit' => 'Kali', 'min_stock' => 0, 'type' => ProductType::Service,
                'units' => [
                    ['unit_name' => 'Kali', 'conversion_factor' => 1, 'barcode' => null, 'price_sell' => 5000, 'is_base' => true, 'is_default_sale' => true],
                ],
                'opening_qty' => 0, 'opening_cost' => 0,
            ],
        ];

        foreach ($products as $data) {
            $product = Product::query()->updateOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'category_id' => $categoryIds[$data['category']],
                    'type' => $data['type'] ?? ProductType::Stock,
                    'base_unit' => $data['base_unit'],
                    'min_stock' => $data['min_stock'],
                    'is_active' => true,
                ]
            );

            foreach ($data['units'] as $unit) {
                ProductUnit::query()->updateOrCreate(
                    ['product_id' => $product->id, 'unit_name' => $unit['unit_name']],
                    $unit
                );
            }

            $alreadySeeded = $product->movements()->where('type', StockMovementType::Opening)->exists();

            if (! $alreadySeeded && $data['opening_qty'] > 0) {
                $stockService->applyIncomingCost(
                    product: $product,
                    locationId: $toko->id,
                    qtyBase: $data['opening_qty'],
                    landedUnitCost: $data['opening_cost'],
                    type: StockMovementType::Opening,
                    note: 'Saldo awal (UAT)',
                    userId: $ownerId,
                );
            }
        }

        $customers = [
            ['code' => 'PLG-0001', 'name' => 'Bu Sari', 'credit_limit' => 500000],
            ['code' => 'PLG-0002', 'name' => 'Pak Joko', 'credit_limit' => 0],
        ];
        foreach ($customers as $data) {
            Customer::query()->firstOrCreate(['code' => $data['code']], [
                'name' => $data['name'],
                'credit_limit' => $data['credit_limit'],
                'is_active' => true,
            ]);
        }

        $suppliers = [
            ['code' => 'SUP-0001', 'name' => 'CV Sumber Rejeki'],
            ['code' => 'SUP-0002', 'name' => 'UD Makmur Jaya'],
        ];
        foreach ($suppliers as $data) {
            Supplier::query()->firstOrCreate(['code' => $data['code']], [
                'name' => $data['name'],
                'is_active' => true,
            ]);
        }
    }
}
