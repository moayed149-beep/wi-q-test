<?php

declare(strict_types=1);

namespace App\GreatFood;

use App\Client\ApiClient;
use App\Model\Product;

class ProductService
{
    public function __construct(private readonly ApiClient $apiClient)
    {
    }

    /**
     * @return Product[]
     */
    public function getProductsByMenuId(int $menuId): array
    {
        $response = $this->apiClient->get("/menu/{$menuId}/products");
        $products = [];

        foreach ($response['data'] ?? [] as $item) {
            $products[] = new Product((int) $item['id'], (string) $item['name']);
        }

        return $products;
    }

    public function updateProduct(int $menuId, Product $product): array
    {
        return $this->apiClient->put(
            "/menu/{$menuId}/product/{$product->id}",
            $product->toArray(),
        );
    }
}
