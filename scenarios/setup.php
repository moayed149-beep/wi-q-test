<?php

declare(strict_types=1);

//Great Food API does not exist

require __DIR__ . '/../vendor/autoload.php';

use App\Client\ApiClient;
use App\GreatFood\AuthenticationService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

const GREAT_FOOD_CLIENT_ID = '1337';
const GREAT_FOOD_CLIENT_SECRET = '4j3g4gj304gj3';

function greatFoodApiClient(array $mockQueue): array
{
    $baseUrl = getenv('GREAT_FOOD_API_URL');

    if (is_string($baseUrl) && $baseUrl !== '') {
        $stack = HandlerStack::create();
        $options = ['base_uri' => $baseUrl, 'handler' => $stack];
    } else {
        $stack = HandlerStack::create(new MockHandler($mockQueue));
        $options = ['handler' => $stack];
    }

    $history = new \ArrayObject();
    $stack->push(Middleware::history($history));

    $apiClient = new ApiClient(new Client($options));

    new AuthenticationService(
        $apiClient,
        getenv('GREAT_FOOD_CLIENT_ID') ?: GREAT_FOOD_CLIENT_ID,
        getenv('GREAT_FOOD_CLIENT_SECRET') ?: GREAT_FOOD_CLIENT_SECRET,
    );

    return [$apiClient, $history];
}

function fixture(string $filename): Response
{
    return new Response(
        200,
        ['Content-Type' => 'application/json'],
        file_get_contents(__DIR__ . '/../responses/' . $filename),
    );
}

if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}
