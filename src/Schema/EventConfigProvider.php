<?php

declare(strict_types= 1);

namespace Marshal\ContentManager\Schema;

final class EventConfigProvider
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
            "schema::event" => [
                "database" => "marshal",
                "description" => "An event",
                "inherits" => ["schema::thing"],
                "name" => "Event",
                "properties" => [],
                "table" => "event",
            ],
        ];
    }
}
