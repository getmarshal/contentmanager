<?php

declare(strict_types= 1);

namespace Marshal\ContentManager\Schema;

final class PlaceConfigProvider
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
            "schema::plave" => [
                "description" => "A place",
                "inherits" => ["schema::thing"],
                "name" => "Place",
                "properties" => [],
            ],
        ];
    }
}
