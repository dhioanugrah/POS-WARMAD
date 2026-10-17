<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\Locations;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LocationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_owner_can_create_location(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('Owner');

        Livewire::actingAs($owner)
            ->test(Locations::class)
            ->call('create')
            ->set('code', 'gdg')
            ->set('name', 'Gudang Belakang')
            ->set('type', 'warehouse')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('locations', ['code' => 'GDG', 'name' => 'Gudang Belakang']);
    }

    public function test_duplicate_code_is_rejected(): void
    {
        Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => 'store']);

        $owner = User::factory()->create();
        $owner->assignRole('Owner');

        Livewire::actingAs($owner)
            ->test(Locations::class)
            ->call('create')
            ->set('code', 'TKO')
            ->set('name', 'Toko Dua')
            ->set('type', 'store')
            ->call('save')
            ->assertHasErrors(['code']);
    }
}
