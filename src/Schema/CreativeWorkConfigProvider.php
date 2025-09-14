<?php

declare(strict_types= 1);

namespace Marshal\ContentManager\Schema;

final class CreativeWorkConfigProvider
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
            "schema::creative-work" => [
                "database" => "marshal",
                "description" => "",
                "inherits" => ["schema::thing"],
                "name" => "Creative Work",
                "properties" => [],
                "table" => "creative_work",
            ],
        ];
    }
}
