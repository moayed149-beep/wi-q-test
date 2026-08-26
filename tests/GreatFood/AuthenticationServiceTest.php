<?php

declare(strict_types=1);

namespace Tests\GreatFood;

use App\Client\ApiClient;
use App\GreatFood\AuthenticationService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class AuthenticationServiceTest extends TestCase
{
    private \ArrayObject $history;

    private function makeApiClient(Response ...$responses): ApiClient
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $this->history = new \ArrayObject();
        $stack->push(Middleware::history($this->history));

        return new ApiClient(new Client(['handler' => $stack]));
    }

    private function tokenResponse(string $token = 'abc123', int $expiresIn = 3600): Response
    {
        return new Response(200, [], json_encode([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $expiresIn,
        ]));
    }

    private function emptyDataResponse(): Response
    {
        return new Response(200, [], json_encode(['data' => []]));
    }

    public function testAuthenticatesLazilyWithFormEncodedCredentials(): void
    {
        $apiClient = $this->makeApiClient($this->tokenResponse(), $this->emptyDataResponse());
        new AuthenticationService($apiClient, '1337', '4j3g4gj304gj3');

        $apiClient->get('/menus');

        $auth = $this->history[0]['request'];
        $this->assertSame('POST', $auth->getMethod());
        $this->assertSame('/auth_token', $auth->getUri()->getPath());
        $this->assertSame('application/x-www-form-urlencoded', $auth->getHeaderLine('Content-Type'));
        $this->assertSame(
            'client_id=1337&client_secret=4j3g4gj304gj3&grant_type=client_credentials',
            (string) $auth->getBody(),
        );

        $this->assertSame('Bearer abc123', $this->history[1]['request']->getHeaderLine('Authorization'));
    }

    public function testReusesTheCachedTokenAcrossRequests(): void
    {
        $apiClient = $this->makeApiClient(
            $this->tokenResponse(),
            $this->emptyDataResponse(),
            $this->emptyDataResponse(),
        );
        new AuthenticationService($apiClient, '1337', '4j3g4gj304gj3');

        $apiClient->get('/menus');
        $apiClient->get('/menu/3/products');

        $authCalls = 0;
        foreach ($this->history as $transaction) {
            if ($transaction['request']->getUri()->getPath() === '/auth_token') {
                $authCalls++;
            }
        }

        $this->assertSame(1, $authCalls, 'Token should be requested exactly once');
    }

    public function testReauthenticatesWhenTheTokenExpires(): void
    {
        $apiClient = $this->makeApiClient(
            $this->tokenResponse('first-token', expiresIn: 60),
            $this->emptyDataResponse(),
            $this->tokenResponse('second-token', expiresIn: 60),
            $this->emptyDataResponse(),
        );

        $now = 1_000_000;
        new AuthenticationService($apiClient, '1337', '4j3g4gj304gj3', clock: function () use (&$now): int {
            return $now;
        });

        $apiClient->get('/menus');
        $now += 120; // beyond the 60s token lifetime

        $apiClient->get('/menus');

        $this->assertSame('Bearer first-token', $this->history[1]['request']->getHeaderLine('Authorization'));
        $this->assertSame('/auth_token', $this->history[2]['request']->getUri()->getPath());
        $this->assertSame('Bearer second-token', $this->history[3]['request']->getHeaderLine('Authorization'));
    }

    public function testThrowsWhenTheResponseHasNoAccessToken(): void
    {
        $apiClient = $this->makeApiClient(
            new Response(200, [], json_encode(['error' => 'nope'])),
        );
        $service = new AuthenticationService($apiClient, '1337', 'wrong-secret');

        $this->expectException(\RuntimeException::class);

        $service->token();
    }
}
