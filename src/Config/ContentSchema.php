<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Config;

use Marshal\ContentManager\Schema\Content;
use Marshal\Utils\Random;

final class ContentSchema
{
    public function __invoke(): array
    {
        return [
            "schema" => [
                "properties" => [
                    Content::ID => $this->getPropertyId(),
                    Content::NAME => $this->getPropertyName(),
                    Content::ALIAS => $this->getPropertyAlias(),
                    Content::DESCRIPTION => $this->getPropertyDescription(),
                    Content::URL => $this->getPropertyUrl(),
                    Content::IMAGE => $this->getPropertyImage(),
                    Content::TAG => $this->getPropertyUniqueAlphaNumericTag(),
                    Content::CREATED_AT => $this->getPropertyCreatedAt(),
                    Content::UPDATED_AT => $this->getPropertyUpdatedAt(),
                ],
            ],
        ];
    }

    private function getPropertyId(): array
    {
        return [
            "autoincrement" => true,
            "description" => "Autoincrementing integer ID",
            "label" => "Auto ID",
            "name" => "id",
            "notnull" => true,
            "type" => "bigint",
        ];
    }

    private function getPropertyName(): array
    {
        return [
            "label" => "Name",
            "description" => "Entry name",
            "name" => "name",
            "notnull" => true,
            "type" => "string",
            "length" => 255,
        ];
    }

    private function getPropertyAlias(): array
    {
        return [
            "label" => "Alias",
            "description" => "Entry alternate name",
            "name" => "alias",
            "type" => "string",
            "length" => 255,
        ];
    }

    private function getPropertyImage(): array
    {
        return [
            "label" => "Image",
            "description" => "Entry featured image",
            "name" => "image",
            "type" => "string",
            "length" => 255,
        ];
    }

    private function getPropertyUrl(): array
    {
        return [
            "label" => "URL",
            "description" => "Entry url",
            "name" => "url",
            "type" => "string",
            "length" => 255,
        ];
    }

    private function getPropertyDescription(): array
    {
        return [
            "label" => "Description",
            "description" => "Entry brief description",
            "name" => "description",
            "type" => "text",
        ];
    }

    private function getPropertyCreatedAt(): array
    {
        return [
            "label" => "Created At",
            "default" => static fn (): \DateTimeImmutable => new \DateTimeImmutable(timezone: new \DateTimeZone('UTC')),
            "description" => "Entry creation time",
            "name" => "created_at",
            "type" => "datetimetz_immutable",
            "notnull" => true,
            "index" => true,
        ];
    }
    private function getPropertyUniqueAlphaNumericTag(): array
    {
        return [
            "constraints" => [
                "unique" => true,
            ],
            "default" => static fn(): string => Random::generateTag(),
            "description" => "A unique alphanumeric identifier",
            "index" => true,
            "label" => "Unique Identifier",
            "length" => 255,
            "name" => "tag",
            "notnull" => true,
            "type" => "string",
        ];
    }

    private function getPropertyUpdatedAt(): array
    {
        return [
            "label" => "Updated At",
            "default" => static fn (): \DateTimeImmutable => new \DateTimeImmutable(timezone: new \DateTimeZone('UTC')),
            "description" => "Entry last updated time",
            "name" => "updated_at",
            "type" => "datetimetz_immutable",
            "index" => true,
        ];
    }
}
