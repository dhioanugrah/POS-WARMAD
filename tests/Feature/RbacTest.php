<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_kasir_can_access_pos_but_not_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Kasir');

        $this->actingAs($user)->get('/pos')->assertOk();
        $this->actingAs($user)->get('/dashboard')->assertForbidden();
    }

    public function test_gudang_can_access_dashboard_but_not_pos(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Gudang');

        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->actingAs($user)->get('/pos')->assertForbidden();
    }

    public function test_owner_can_access_everything_via_gate_before(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Owner');

        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->actingAs($user)->get('/pos')->assertOk();
    }

    public function test_registration_route_does_not_exist(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_login_rate_limited_after_five_attempts(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            Volt::test('pages.auth.login')
                ->set('form.login', $user->email)
                ->set('form.password', 'wrong-password')
                ->call('login');
        }

        $component = Volt::test('pages.auth.login')
            ->set('form.login', $user->email)
            ->set('form.password', 'wrong-password')
            ->call('login');

        $component->assertHasErrors('form.login');
    }
}
