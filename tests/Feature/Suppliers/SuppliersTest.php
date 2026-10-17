<?php

namespace Tests\Feature\Suppliers;

use App\Livewire\Suppliers\Index;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SuppliersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_can_create_supplier_with_auto_code_uat_mst_09(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Gudang');

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('create')
            ->set('name', 'Toko Grosir Abadi')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('suppliers', ['name' => 'Toko Grosir Abadi', 'code' => 'SUP-0001']);
    }

    public function test_kasir_cannot_access_suppliers(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Kasir');

        $this->actingAs($user)->get('/suppliers')->assertForbidden();
    }
}
