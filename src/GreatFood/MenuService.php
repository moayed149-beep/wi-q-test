<?php

declare(strict_types=1);

namespace App\GreatFood;

use App\Client\ApiClient;

class MenuService
{
    public function __construct(private readonly ApiClient $apiClient)
    {
    }

    public function getMenus(): array
    {
        return $this->apiClient->get('/menus');
    }

    public function findMenuIdByName(string $name): ?int
    {
        $menus = $this->getMenus();

        foreach ($menus['data'] ?? [] as $menu) {
            if (strcasecmp($menu['name'], $name) === 0) {
                return (int) $menu['id'];
            }
        }

        return null;
    }
}
