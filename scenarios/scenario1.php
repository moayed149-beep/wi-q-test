<?php

declare(strict_types=1);

/**
 * Scenario 1: list the products of the "Takeaway" menu as a table.
 *
 * Run: php scenarios/scenario1.php
 */

require __DIR__ . '/setup.php';

use App\GreatFood\MenuService;
use App\GreatFood\ProductService;

[$apiClient] = greatFoodApiClient([
    fixture('token.json'),
    fixture('menus.json'),
    fixture('menu-products.json'),
]);

try {
    $menuId = (new MenuService($apiClient))->findMenuIdByName('Takeaway');

    if ($menuId === null) {
        echo "No menu named \"Takeaway\" was found.\n";
        exit(1);
    }

    $products = (new ProductService($apiClient))->getProductsByMenuId($menuId);

    echo "Products on the \"Takeaway\" menu (id {$menuId}):\n\n";
    echo "| ID | Name         |\n";
    echo "|----|--------------|\n";
    foreach ($products as $product) {
        printf("| %-2d | %-12s |\n", $product->id, $product->name);
    }
} catch (\Throwable $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}
