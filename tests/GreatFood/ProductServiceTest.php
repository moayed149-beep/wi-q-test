<?php

declare(strict_types=1);

namespace Tests\GreatFood;

use App\Client\ApiClient;
use App\GreatFood\ProductService;
use App\Model\Product;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ProductServiceTest extends TestCase
{
    private \ArrayObject $history;

    private function makeService(Response ...$responses): ProductService
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $this->history = new \ArrayObject();
        $stack->push(Middleware::history($this->history));

        $apiClient = new ApiClient(new Client(['handler' => $stack]));
        $apiClient->setTokenProvider(static fn (): string => 'Bearer test-token');

        return new ProductService($apiClient);
    }

    public function testGetProductsByMenuIdReturnsProductModels(): void
    {
        $service = $this->makeService(new Response(200, [], json_encode([
            'data' => [
                ['id' => 4, 'name' => 'Burger'],
                ['id' => 5, 'name' => 'Chips'],
            ],
        ])));

        $products = $service->getProductsByMenuId(3);

        $this->assertCount(2, $products);
        $this->assertInstanceOf(Product::class, $products[0]);
        $this->assertSame(4, $products[0]->id);
        $this->assertSame('Burger', $products[0]->name);
        $this->assertSame('/menu/3/products', $this->history[0]['request']->getUri()->getPath());
    }

    public function testUpdateProductSendsAuthenticatedJsonPut(): void
    {
        $service = $this->makeService(new Response(200, [], json_encode([
            'data' => ['id' => 84, 'name' => 'Chips'],
        ])));

        $response = $service->updateProduct(7, new Product(84, 'Chips'));

        $this->assertSame('Chips', $response['data']['name']);

        $request = $this->history[0]['request'];
        $this->assertSame('PUT', $request->getMethod());
        $this->assertSame('/menu/7/product/84', $request->getUri()->getPath());
        $this->assertSame('Bearer test-token', $request->getHeaderLine('Authorization'));
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertSame(['id' => 84, 'name' => 'Chips'], json_decode((string) $request->getBody(), true));
    }
}
