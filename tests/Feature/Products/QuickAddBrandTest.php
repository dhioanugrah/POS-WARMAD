<?php

namespace Tests\Feature\Products;

use App\Livewire\Products\Form;
use App\Models\Brand;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuickAddBrandTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_quick_add_brand_from_product_form(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $component = Livewire::actingAs($admin)
            ->test(Form::class)
            ->call('openQuickAddBrand')
            ->set('quickAddBrandName', 'Indofood')
            ->call('quickAddBrand')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('brands', ['name' => 'Indofood']);
        $brandId = Brand::where('name', 'Indofood')->value('id');
        $component->assertSet('brandId', $brandId);
        $component->assertSet('showQuickAddBrand', false);
    }

    public function test_duplicate_brand_name_is_rejected(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Brand::create(['name' => 'Indofood']);

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        Livewire::actingAs($admin)
            ->test(Form::class)
            ->call('openQuickAddBrand')
            ->set('quickAddBrandName', 'Indofood')
            ->call('quickAddBrand')
            ->assertHasErrors(['quickAddBrandName']);
    }
}
