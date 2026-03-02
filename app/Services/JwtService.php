<?php

namespace App\Services;

use App\Models\User;
use InvalidArgumentException;
use RuntimeException;

class JwtService
{
    private string $secret;
    private int $ttl;
    private int $refreshTtl;
    private string $algorithm = 'sha256';

    public function __construct()
    {
        $this->secret = config('jwt.secret');
        $this->ttl = config('jwt.ttl');
        $this->refreshTtl = config('jwt.refresh_ttl');

        if (empty($this->secret)) {
            throw new RuntimeException('JWT secret is not configured.');
        }
    }

    /**
     * Generate a JWT token for the given user.
     */
    public function encode(User $user): string
    {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ];

        $now = time();

        $payload = [
            'sub' => $user->getJwtIdentifier(),
            'iat' => $now,
            'exp' => $now + ($this->ttl * 60),
            ...$user->getJwtCustomClaims(),
        ];

        $segments = [];
        $segments[] = $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR));
        $segments[] = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));

        $signingInput = implode('.', $segments);
        $signature = $this->sign($signingInput);
        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    /**
     * Decode and validate a JWT token. Returns the payload array.
     *
     * @throws InvalidArgumentException if token structure is invalid
     * @throws RuntimeException if signature verification fails or token is expired
     */
    public function decode(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new InvalidArgumentException('Invalid JWT: must contain exactly 3 segments.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        // Verify signature FIRST before trusting any payload data
        $signingInput = "{$encodedHeader}.{$encodedPayload}";
        $signature = $this->base64UrlDecode($encodedSignature);

        if (!$this->verify($signingInput, $signature)) {
            throw new RuntimeException('Invalid JWT: signature verification failed.');
        }

        // Decode header and verify algorithm
        $header = json_decode($this->base64UrlDecode($encodedHeader), true, 512, JSON_THROW_ON_ERROR);

        if (($header['alg'] ?? null) !== 'HS256') {
            throw new RuntimeException('Invalid JWT: unsupported algorithm.');
        }

        // Decode payload
        $payload = json_decode($this->base64UrlDecode($encodedPayload), true, 512, JSON_THROW_ON_ERROR);

        // Check expiration
        if (!isset($payload['exp']) || $payload['exp'] < time()) {
            throw new RuntimeException('Invalid JWT: token has expired.');
        }

        return $payload;
    }

    /**
     * Decode a token WITHOUT checking expiration. Used by refresh logic.
     * Signature is still verified. Returns null if invalid or outside refresh window.
     */
    public function decodeForRefresh(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        $signingInput = "{$encodedHeader}.{$encodedPayload}";
        $signature = $this->base64UrlDecode($encodedSignature);

        if (!$this->verify($signingInput, $signature)) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($encodedPayload), true, 512, JSON_THROW_ON_ERROR);

        // Check refresh window: token must not be older than refresh_ttl
        $issuedAt = $payload['iat'] ?? 0;
        if ($issuedAt + ($this->refreshTtl * 60) < time()) {
            return null;
        }

        return $payload;
    }

    /**
     * Create the HMAC-SHA256 signature (raw binary).
     */
    private function sign(string $input): string
    {
        return hash_hmac($this->algorithm, $input, $this->secret, true);
    }

    /**
     * Verify that the signature matches the input (timing-safe).
     */
    private function verify(string $input, string $signature): bool
    {
        $expected = $this->sign($input);
        return hash_equals($expected, $signature);
    }

    /**
     * Base64url encode per RFC 4648 Section 5.
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64url decode per RFC 4648 Section 5.
     */
    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder !== 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($data, '-_', '+/'), true);

        if ($decoded === false) {
            throw new InvalidArgumentException('Invalid base64url data.');
        }

        return $decoded;
    }
}
