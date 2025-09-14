<?php

declare(strict_types= 1);

namespace Marshal\ContentManager\Schema;

final class ProductConfigProvider
{
    public function __invoke(): array
    {
        return [
            "schema" => [
                "properties"    => $this->getProperties(),
                "types"         => $this->getType(),
            ],
        ];
    }

    private function getProperties(): array
    {
        return [];
    }

    private function getType(): array
    {
        return [
            "schema::product" => [
                "database" => "marshal",
                "description" => "A product",
                "inherits" => ["schema::thing"],
                "name" => "Product",
                "properties" => [],
                "table" => "product",
            ],
        ];
    }
}
