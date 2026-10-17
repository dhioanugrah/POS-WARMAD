<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\Users;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UsersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_owner_can_create_a_gudang_user_uat_auth_07(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('Owner');

        Livewire::actingAs($owner)
            ->test(Users::class)
            ->call('create')
            ->set('name', 'Rudi')
            ->set('email', 'rudi@pos.com')
            ->set('role', 'Gudang')
            ->set('password', 'password123')
            ->call('save')
            ->assertHasNoErrors();

        $rudi = User::where('email', 'rudi@pos.com')->first();
        $this->assertNotNull($rudi);
        $this->assertTrue($rudi->hasRole('Gudang'));

        $this->actingAs($rudi)->get('/dashboard')->assertOk();
        $this->actingAs($rudi)->get('/pos')->assertForbidden();
    }

    public function test_owner_cannot_deactivate_self(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('Owner');

        Livewire::actingAs($owner)
            ->test(Users::class)
            ->call('toggleActive', $owner->id);

        $this->assertTrue($owner->fresh()->is_active);
    }

    public function test_non_owner_cannot_manage_users(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $this->actingAs($admin)->get('/settings/users')->assertForbidden();
    }
}
