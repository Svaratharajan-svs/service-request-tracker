<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_login(): void
    {
        User::factory()->create([
            'email' => 'employee@example.com',
            'password' => Hash::make('Password@123'),
        ]);

        $response = $this
            ->withHeader('Origin', 'http://localhost:3000')
            ->postJson('/api/auth/login', [
                'email' => 'employee@example.com',
                'password' => 'Password@123',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.user.email',
                'employee@example.com'
            );
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        User::factory()->create([
            'email' => 'employee@example.com',
            'password' => Hash::make('Password@123'),
        ]);

        $response = $this
            ->withHeader('Origin', 'http://localhost:3000')
            ->postJson('/api/auth/login', [
                'email' => 'employee@example.com',
                'password' => 'WrongPassword',
            ]);

        $response->assertUnprocessable();
    }

    public function test_unauthenticated_user_cannot_access_me(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_access_me(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->getJson('/api/auth/me');

        $response
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id);
    }
}