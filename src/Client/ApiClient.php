<?php

declare(strict_types=1);

namespace App\Client;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Thin wrapper around Guzzle: attaches the Authorization header via a
 * token provider and decodes JSON responses. Guzzle throws on 4xx/5xx
 * (http_errors), so error handling stays with the caller via
 * GuzzleHttp exceptions.
 */
class ApiClient
{
    /** @var null|callable(): string returns the Authorization header value */
    private $tokenProvider = null;

    public function __construct(private readonly ClientInterface $client)
    {
    }

    /**
     * The provider is asked for the Authorization header on every
     * authenticated request, which lets it refresh expired tokens
     * transparently. POST /auth_token itself bypasses it.
     */
    public function setTokenProvider(?callable $tokenProvider): void
    {
        $this->tokenProvider = $tokenProvider;
    }

    public function get(string $uri): array
    {
        return $this->decode(
            $this->client->request('GET', $uri, $this->buildOptions()),
        );
    }

    public function post(string $uri, array $formParams): array
    {
        return $this->decode(
            $this->client->request('POST', $uri, ['form_params' => $formParams]),
        );
    }

    public function put(string $uri, array $jsonPayload): array
    {
        return $this->decode(
            $this->client->request('PUT', $uri, $this->buildOptions() + ['json' => $jsonPayload]),
        );
    }

    private function buildOptions(): array
    {
        if ($this->tokenProvider === null) {
            return [];
        }

        return ['headers' => ['Authorization' => ($this->tokenProvider)()]];
    }

    private function decode(ResponseInterface $response): array
    {
        $decoded = json_decode($response->getBody()->getContents(), true);

        if (!is_array($decoded)) {
            throw new \RuntimeException('API returned a non-JSON or non-object response');
        }

        return $decoded;
    }
}
