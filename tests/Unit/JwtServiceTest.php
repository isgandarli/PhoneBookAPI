<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JwtServiceTest extends TestCase
{
    use RefreshDatabase;

    private JwtService $jwtService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jwtService = new JwtService();
    }

    public function test_encode_returns_three_segment_token(): void
    {
        $user = User::factory()->create();
        $token = $this->jwtService->encode($user);

        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
    }

    public function test_decode_returns_valid_payload(): void
    {
        $user = User::factory()->create();
        $token = $this->jwtService->encode($user);
        $payload = $this->jwtService->decode($token);

        $this->assertEquals($user->id, $payload['sub']);
        $this->assertEquals($user->username, $payload['username']);
        $this->assertArrayHasKey('iat', $payload);
        $this->assertArrayHasKey('exp', $payload);
    }

    public function test_decode_rejects_tampered_token(): void
    {
        $user = User::factory()->create();
        $token = $this->jwtService->encode($user);

        $parts = explode('.', $token);
        $parts[1] = $parts[1] . 'tampered';
        $tampered = implode('.', $parts);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('signature verification failed');
        $this->jwtService->decode($tampered);
    }

    public function test_decode_rejects_expired_token(): void
    {
        config(['jwt.ttl' => -1]);
        $service = new JwtService();

        $user = User::factory()->create();
        $token = $service->encode($user);

        config(['jwt.ttl' => 60]);
        $service2 = new JwtService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('token has expired');
        $service2->decode($token);
    }

    public function test_decode_rejects_invalid_structure(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->jwtService->decode('not.a.valid.token.with.too.many.parts');
    }

    public function test_decode_for_refresh_allows_expired_token(): void
    {
        config(['jwt.ttl' => -1]);
        $service = new JwtService();

        $user = User::factory()->create();
        $token = $service->encode($user);

        config(['jwt.ttl' => 60]);
        $service2 = new JwtService();

        $payload = $service2->decodeForRefresh($token);
        $this->assertNotNull($payload);
        $this->assertEquals($user->id, $payload['sub']);
    }

    public function test_decode_for_refresh_rejects_tampered_token(): void
    {
        $user = User::factory()->create();
        $token = $this->jwtService->encode($user);

        $parts = explode('.', $token);
        $parts[1] = $parts[1] . 'x';
        $tampered = implode('.', $parts);

        $result = $this->jwtService->decodeForRefresh($tampered);
        $this->assertNull($result);
    }
}
