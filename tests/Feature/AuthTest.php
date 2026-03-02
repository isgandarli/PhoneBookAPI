<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_valid_credentials(): void
    {
        User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'testuser',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
            ])
            ->assertJson(['token_type' => 'Bearer']);
    }

    public function test_login_with_invalid_password(): void
    {
        User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'testuser',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized']);
    }

    public function test_login_with_nonexistent_user(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'username' => 'nobody',
            'password' => 'password',
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized']);
    }

    public function test_login_validates_required_fields(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username', 'password']);
    }

    public function test_me_returns_user_with_valid_token(): void
    {
        $user = User::factory()->create(['username' => 'testuser']);
        $token = app(JwtService::class)->encode($user);

        $response = $this->getJson('/api/auth/me', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'username' => 'testuser',
            ]);
    }

    public function test_me_returns_401_without_token(): void
    {
        $response = $this->getJson('/api/auth/me');
        $response->assertStatus(401);
    }

    public function test_me_returns_401_with_invalid_token(): void
    {
        $response = $this->getJson('/api/auth/me', [
            'Authorization' => 'Bearer invalid.token.here',
        ]);
        $response->assertStatus(401);
    }

    public function test_logout_returns_success(): void
    {
        $user = User::factory()->create();
        $token = app(JwtService::class)->encode($user);

        $response = $this->postJson('/api/auth/logout', [], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Successfully logged out.']);
    }

    public function test_refresh_issues_new_token(): void
    {
        $user = User::factory()->create();
        $token = app(JwtService::class)->encode($user);

        // Wait 1 second so the new token gets a different iat/exp timestamp
        sleep(1);

        $response = $this->postJson('/api/auth/refresh', [], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in']);

        $this->assertNotEquals($token, $response->json('access_token'));
    }
}
