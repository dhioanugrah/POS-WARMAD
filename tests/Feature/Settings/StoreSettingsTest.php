<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\StoreSettings;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StoreSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, SettingsSeeder::class]);
    }

    public function test_owner_can_update_store_profile(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('Owner');

        Livewire::actingAs($owner)
            ->test(StoreSettings::class)
            ->set('storeName', 'Toko Berkah')
            ->set('storeAddress', 'Jl. Merdeka No. 1')
            ->set('storePhone', '0812345678')
            ->set('paperWidth', 80)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Toko Berkah', setting('store.name'));
        $this->assertSame(80, setting('receipt.paper_width'));
    }

    public function test_kasir_cannot_access_settings_route(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Kasir');

        $this->actingAs($user)->get('/settings')->assertForbidden();
    }

    public function test_document_prefix_can_be_changed(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('Owner');

        Livewire::actingAs($owner)
            ->test(StoreSettings::class)
            ->set('docPrefixes.sale', 'inv')
            ->call('save');

        $this->assertSame('INV', setting('doc.prefix.sale'));
    }
}
