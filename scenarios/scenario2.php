<?php
// Scenario 2: fix the typo on product 84 of menu 7 ("Chpis" -> "Chips")

declare(strict_types=1);

require __DIR__ . '/setup.php';

use App\GreatFood\ProductService;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

const MENU_ID = 7;
const PRODUCT_ID = 84;

[$apiClient, $history] = greatFoodApiClient([
    fixture('token.json'),
    fixture('menu-products.json'),
    // The mock PUT endpoint echoes the submitted product back, as a real API would.
    static fn (RequestInterface $request) => new Response(
        200,
        ['Content-Type' => 'application/json'],
        json_encode(['data' => json_decode((string) $request->getBody(), true)]),
    ),
]);

try {
    $productService = new ProductService($apiClient);

    // Fetch the current product so the PUT body matches the full GET model.
    $current = null;
    foreach ($productService->getProductsByMenuId(MENU_ID) as $product) {
        if ($product->id === PRODUCT_ID) {
            $current = $product;
            break;
        }
    }

    if ($current === null) {
        echo sprintf("Product %d not found on menu %d.\n", PRODUCT_ID, MENU_ID);
        exit(1);
    }

    echo sprintf("Before: product %d is named \"%s\"\n", $current->id, $current->name);

    $response = $productService->updateProduct(MENU_ID, new \App\Model\Product($current->id, 'Chips'));

    echo sprintf("After:  product %d is named \"%s\"\n\n", $current->id, $response['data']['name'] ?? 'Chips');

    echo "--- Proof of request ---\n";
    $transaction = $history[count($history) - 1];
    $request = $transaction['request'];

    echo sprintf("%s %s\n", $request->getMethod(), $request->getUri()->getPath());
    foreach (['Authorization', 'Content-Type'] as $header) {
        echo sprintf("%s: %s\n", $header, $request->getHeaderLine($header));
    }
    echo "\n" . (string) $request->getBody() . "\n\n";
    echo sprintf("Response status: %d\n", $transaction['response']->getStatusCode());
    echo 'Response body: ' . (string) $transaction['response']->getBody() . "\n";
} catch (\Throwable $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}
