<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Schema;

use Marshal\Database\Schema\Type;

class Content extends Type
{
    /* properties */
    public const string ID = "item::id";
    public const string NAME = "item::name";
    public const string ALIAS = "item::alias";
    public const string DESCRIPTION = "item::description";
    public const string URL = "item::url";
    public const string IMAGE = "item::image";
    public const string CREATED_AT = "item::created_at";
    public const string TAG = "item::unique_tag";
    public const string UPDATED_AT = "item::updated_at";

    public function getAlias(): ?string
    {
        return $this->getProperty(self::ALIAS)->getValue();
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->getProperty(self::CREATED_AT)->getValue();
    }

    public function getDescription(): string
    {
        return $this->getProperty(self::DESCRIPTION)->getValue();
    }

    public function getId(): int
    {
        return $this->getProperty(self::ID)->getValue();
    }

    public function getImage(): ?string
    {
        return $this->getProperty(self::IMAGE)->getValue();
    }

    public function getName(): string
    {
        return $this->getProperty(self::NAME)->getValue();
    }

    public function getTag(): string
    {
        return $this->getProperty(self::TAG)->getValue();
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->getProperty(self::UPDATED_AT)->getValue();
    }

    public function getUrl(): ?string
    {
        return $this->getProperty(self::URL)->getValue();
    }
}
