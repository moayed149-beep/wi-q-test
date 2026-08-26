<?php

declare(strict_types=1);

namespace App\Auth;

/**
 * OAuth access token with its expiry time, so the client knows
 * when to re-authenticate instead of failing with a 401.
 */
final class AccessToken
{
    private const EXPIRY_LEEWAY_SECONDS = 30;

    public function __construct(
        public readonly string $token,
        public readonly string $type,
        public readonly int $expiresAt,
    ) {
    }

    public static function fromApiResponse(array $payload, int $now): self
    {
        $token = $payload['access_token'] ?? '';

        if (!is_string($token) || $token === '') {
            throw new \RuntimeException('Auth response did not contain an access_token');
        }

        return new self(
            $token,
            $payload['token_type'] ?? 'Bearer',
            $now + (int) ($payload['expires_in'] ?? 0),
        );
    }

    public function isExpired(int $now): bool
    {
        return $now >= $this->expiresAt - self::EXPIRY_LEEWAY_SECONDS;
    }

    public function authorizationHeader(): string
    {
        return $this->type . ' ' . $this->token;
    }
}
