<?php

namespace Tests\Feature\Products;

use App\Livewire\Products\Form;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuickAddCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_quick_add_category_from_product_form(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $component = Livewire::actingAs($admin)
            ->test(Form::class)
            ->call('openQuickAddCategory')
            ->set('quickAddCategoryName', 'Minuman')
            ->call('quickAddCategory')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', ['name' => 'Minuman']);
        $categoryId = Category::where('name', 'Minuman')->value('id');
        $component->assertSet('categoryId', $categoryId);
        $component->assertSet('showQuickAddCategory', false);
    }

    public function test_duplicate_category_name_is_rejected(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Category::create(['name' => 'Minuman']);

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        Livewire::actingAs($admin)
            ->test(Form::class)
            ->call('openQuickAddCategory')
            ->set('quickAddCategoryName', 'Minuman')
            ->call('quickAddCategory')
            ->assertHasErrors(['quickAddCategoryName']);
    }
}
