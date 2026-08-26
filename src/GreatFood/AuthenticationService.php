<?php

declare(strict_types=1);

namespace App\GreatFood;

use App\Auth\AccessToken;
use App\Client\ApiClient;

/**
 * OAuth client-credentials flow, handled transparently.
 *
 * Constructing this service registers it as the ApiClient's token
 * provider: the first authenticated request triggers the token POST,
 * the token is then cached and reused, and a new one is fetched
 * automatically shortly before the old one expires. Callers never
 * deal with tokens at all.
 */
class AuthenticationService
{
    private ?AccessToken $accessToken = null;

    /** @var \Closure(): int */
    private readonly \Closure $clock;

    public function __construct(
        private readonly ApiClient $apiClient,
        private readonly string $clientId,
        private readonly string $clientSecret,
        ?\Closure $clock = null,
    ) {
        $this->clock = $clock ?? static fn (): int => time();
        $this->apiClient->setTokenProvider(fn (): string => $this->token()->authorizationHeader());
    }

    public function token(): AccessToken
    {
        $now = ($this->clock)();

        if ($this->accessToken === null || $this->accessToken->isExpired($now)) {
            $this->accessToken = $this->requestToken($now);
        }

        return $this->accessToken;
    }

    private function requestToken(int $now): AccessToken
    {
        $response = $this->apiClient->post('/auth_token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'client_credentials',
        ]);

        return AccessToken::fromApiResponse($response, $now);
    }
}
