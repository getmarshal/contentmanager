<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Schema;

use Marshal\Database\Type;
use Marshal\Utils\Schema;

class Thing extends Type
{
    public function getAlias(): string
    {
        return $this->getProperty(Schema::PROPERTY_ALIAS)->getValue();
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->getProperty(Schema::PROPERTY_CREATED_AT)->getValue();
    }

    public function getDescription(): string
    {
        return $this->getProperty(Schema::PROPERTY_DESCRIPTION)->getValue();
    }

    public function getId(): int
    {
        return $this->getProperty(Schema::PROPERTY_AUTO_ID)->getValue();
    }

    public function getImage(): string
    {
        return $this->getProperty(Schema::PROPERTY_IMAGE)->getValue();
    }

    public function getName(): string
    {
        return $this->getProperty(Schema::PROPERTY_NAME)->getValue();
    }

    public function getTag(): string
    {
        return $this->getProperty(Schema::PROPERTY_UNIQUE_ALPHANUMERIC_TAG)->getValue();
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->getProperty(Schema::PROPERTY_UPDATED_AT)->getValue();
    }

    public function getUrl(): string
    {
        return $this->getProperty(Schema::PROPERTY_URL)->getValue();
    }
}
