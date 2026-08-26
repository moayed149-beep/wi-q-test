<?php

declare(strict_types=1);

namespace Tests\GreatFood;

use App\Client\ApiClient;
use App\GreatFood\MenuService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class MenuServiceTest extends TestCase
{
    private function makeService(Response $response): MenuService
    {
        $stack = HandlerStack::create(new MockHandler([$response]));

        return new MenuService(new ApiClient(new Client(['handler' => $stack])));
    }

    public function testFindMenuIdByNameReturnsCorrectId(): void
    {
        $service = $this->makeService(new Response(200, [], json_encode([
            'data' => [
                ['id' => 1, 'name' => 'Starters'],
                ['id' => 3, 'name' => 'Takeaway'],
            ],
        ])));

        $this->assertSame(3, $service->findMenuIdByName('Takeaway'));
    }

    public function testFindMenuIdByNameIsCaseInsensitive(): void
    {
        $service = $this->makeService(new Response(200, [], json_encode([
            'data' => [['id' => 3, 'name' => 'Takeaway']],
        ])));

        $this->assertSame(3, $service->findMenuIdByName('takeaway'));
    }

    public function testFindMenuIdByNameReturnsNullWhenMissing(): void
    {
        $service = $this->makeService(new Response(200, [], json_encode(['data' => []])));

        $this->assertNull($service->findMenuIdByName('Breakfast'));
    }

    public function testServerErrorsSurfaceAsGuzzleExceptions(): void
    {
        $service = $this->makeService(new Response(401, [], '{"error":"unauthorized"}'));

        $this->expectException(ClientException::class);

        $service->getMenus();
    }
}
