<?php

declare(strict_types=1);

namespace App\Model;

class Product
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {
    }

    /**
     * Shape expected by the PUT endpoint (same as the GET response model).
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
