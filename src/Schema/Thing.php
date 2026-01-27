<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Schema;

use Marshal\ContentManager\SchemaConfig\ThingSchema;
use Marshal\Database\Schema\Type;

class Thing extends Type
{
    public function getAlias(): ?string
    {
        return $this->getProperty(ThingSchema::PROPERTY_ALIAS)->getValue();
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->getProperty(ThingSchema::PROPERTY_CREATED_AT)->getValue();
    }

    public function getDescription(): string
    {
        return $this->getProperty(ThingSchema::PROPERTY_DESCRIPTION)->getValue();
    }

    public function getId(): int
    {
        return $this->getProperty(ThingSchema::PROPERTY_AUTO_ID)->getValue();
    }

    public function getImage(): ?string
    {
        return $this->getProperty(ThingSchema::PROPERTY_IMAGE)->getValue();
    }

    public function getName(): string
    {
        return $this->getProperty(ThingSchema::PROPERTY_NAME)->getValue();
    }

    public function getTag(): string
    {
        return $this->getProperty(ThingSchema::PROPERTY_UNIQUE_TAG)->getValue();
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->getProperty(ThingSchema::PROPERTY_UPDATED_AT)->getValue();
    }

    public function getUrl(): string
    {
        return $this->getProperty(ThingSchema::PROPERTY_URL)->getValue();
    }
}
