<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_register_returns_404(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(404);
    }

    public function test_timezone_is_asia_jakarta(): void
    {
        $this->assertEquals('Asia/Jakarta', config('app.timezone'));
        $this->assertEquals('Asia/Jakarta', now()->timezone->getName());
    }
}
