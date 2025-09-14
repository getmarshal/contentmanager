<?php

declare(strict_types= 1);

namespace Marshal\ContentManager\Schema;

use Doctrine\DBAL\Types\Types;

final class ThingConfigProvider
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
        return [
            "schema::id" => [
                "autoincrement" => true,
                "description" => "Autoincrementing integer ID",
                "label" => "Id",
                "name" => "id",
                "notnull" => true,
                "type" => Types::BIGINT,
            ],
            "schema::name" => [
                "allowEmpty" => false,
                "description" => "Entry name",
                "label" => "Name",
                "length" => 255,
                "name" => "name",
                "notnull" => true,
                "type" => Types::STRING,
            ],
            "schema::alias" => [
                "description" => "Entry alternate name",
                "label" => "Alias",
                "length" => 255,
                "name" => "alias",
                "type" => Types::STRING,
            ],
            "schema::description" => [
                "description" => "Entry brief description",
                "label" => "Description",
                "name" => "description",
                "type" => Types::TEXT,
            ],
            "schema::identifier" => [
                "constraints" => [
                    "unique" => true,
                ],
                "description" => "Entry unique alphanumeric identifier",
                "index" => true,
                "label" => "Unique Identifier",
                "length" => 255,
                "name" => "identifier",
                "notnull" => true,
                "type" => Types::STRING,
            ],
            "schema::image" => [
                "description" => "Entry featured image",
                "label" => "Image",
                "length" => 255,
                "name" => "image",
                "type" => Types::STRING,
            ],
            "schema::url" => [
                "description" => "Entry url",
                "label" => "URL",
                "length" => 255,
                "name" => "url",
                "type" => Types::STRING,
            ],
            "schema::created_at" => [
                "description" => "Entry creation time",
                "index" => true,
                "label" => "Created At",
                "name" => "created_at",
                "notnull" => true,
                "type" => Types::DATETIMETZ_IMMUTABLE,
            ],
            "schema::updated_at" => [
                "description" => "Entry last updated time",
                "index" => true,
                "label" => "Updated At",
                "name" => "updated_at",
                "notnull" => true,
                "type" => Types::DATETIMETZ_IMMUTABLE,
            ],
        ];
    }

    private function getType(): array
    {
        return [
            "schema::thing" => [
                "description" => "The most generic item.",
                "name" => "Thing",
                "properties" => [
                    "schema::id",
                    "schema::name",
                    "schema::alias",
                    "schema::description",
                    "schema::image",
                    "schema::url",
                    "schema::created_at",
                    "schema::updated_at",
                ],
            ],
        ];
    }
}
