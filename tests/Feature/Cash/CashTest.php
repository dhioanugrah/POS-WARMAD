<?php

namespace Tests\Feature\Cash;

use App\Livewire\Cash\Categories;
use App\Livewire\Cash\Index;
use App\Models\CashCategory;
use App\Models\CashTransaction;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CashTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, SettingsSeeder::class]);

        Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => 'store']);
        $this->admin = User::factory()->create(['default_location_id' => 1]);
        $this->admin->assignRole('Admin');
    }

    public function test_can_record_cash_out_uat_fin_01(): void
    {
        $category = CashCategory::create(['name' => 'Listrik & Air', 'type' => 'out', 'is_active' => true]);

        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->call('create')
            ->set('cashCategoryId', $category->id)
            ->set('method', 'bank')
            ->set('amount', 350000)
            ->set('description', 'Listrik & Air')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('cash_transactions', ['amount' => 350000, 'method' => 'bank']);
        $this->assertStringStartsWith('KK-', CashTransaction::first()->number);
    }

    public function test_can_add_and_use_category_uat_fin_02(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Categories::class)
            ->call('create')
            ->set('name', 'Iuran Kebersihan')
            ->set('type', 'out')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('cash_categories', ['name' => 'Iuran Kebersihan']);
    }

    public function test_used_category_cannot_be_deleted_only_deactivated(): void
    {
        $category = CashCategory::create(['name' => 'Sewa', 'type' => 'out', 'is_active' => true]);
        CashTransaction::create([
            'number' => 'KK-TKO-260926-0001', 'location_id' => 1, 'user_id' => $this->admin->id,
            'cash_category_id' => $category->id, 'type' => 'out', 'method' => 'cash', 'amount' => 100000, 'occurred_at' => now(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(Categories::class)
            ->call('delete', $category->id);

        $this->assertDatabaseHas('cash_categories', ['id' => $category->id]);
    }
}
