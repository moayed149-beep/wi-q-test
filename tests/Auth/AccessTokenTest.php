<?php

declare(strict_types=1);

namespace Tests\Auth;

use App\Auth\AccessToken;
use PHPUnit\Framework\TestCase;

class AccessTokenTest extends TestCase
{
    public function testBuildsFromAnApiResponse(): void
    {
        $token = AccessToken::fromApiResponse([
            'access_token' => 'abc123',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], now: 1000);

        $this->assertSame('abc123', $token->token);
        $this->assertSame(4600, $token->expiresAt);
        $this->assertSame('Bearer abc123', $token->authorizationHeader());
    }

    public function testIsNotExpiredWellBeforeExpiry(): void
    {
        $token = new AccessToken('abc', 'Bearer', expiresAt: 5000);

        $this->assertFalse($token->isExpired(now: 1000));
    }

    public function testExpiresSlightlyEarlyToAllowForClockDriftAndLatency(): void
    {
        $token = new AccessToken('abc', 'Bearer', expiresAt: 5000);

        $this->assertTrue($token->isExpired(now: 4980), 'Should expire within the 30s leeway window');
        $this->assertTrue($token->isExpired(now: 5001));
    }

    public function testRejectsAResponseWithoutAnAccessToken(): void
    {
        $this->expectException(\RuntimeException::class);

        AccessToken::fromApiResponse(['token_type' => 'Bearer'], now: 1000);
    }
}
