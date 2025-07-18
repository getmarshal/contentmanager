<?php

declare(strict_types=1);

namespace Marshal\ContentManager;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Marshal\Database\Schema\Property;
use Marshal\Database\Schema\Type;

class Content
{
    public function __construct(private ContentConfig $config)
    {
    }

    public function get(string $property, mixed $default = null): mixed
    {
        if (! $this->getType()->hasProperty($property)) {
            return $default;
        }

        return $this->getType()->getProperty($property)->getValue();
    }

    public function getConfig(): ContentConfig
    {
        return $this->config;
    }

    public function getType(): Type
    {
        return $this->getConfig()->getType();
    }

    public function hydrate(array $row, AbstractPlatform $databasePlatform): void
    {
        foreach ($row as $key => $value) {
            foreach ($this->getType()->getProperties() as $property) {
                if ($key !== $property->getIdentifier()) {
                    continue;
                }

                if (! $property->hasRelation()) {
                    $property->setValue(
                        $property->getDatabaseType()->convertToPHPValue($value, $databasePlatform)
                    );
                } else {
                    $property->setValue($this->hydrateRelation($property, $row));
                }
            }
        }
    }

    public function toArray(): array
    {
        $values = [];
        foreach ($this->getType()->getProperties() as $property) {
            $value = $property->getValue();
            $values[$property->getIdentifier()] = $value instanceof self
                ? $value->toArray()
                : $value;
        }

        return $values;
    }

    private function hydrateRelation(Property $property, array $data): self
    {
        $content = new self($property->getRelation()->getRelationConfig());
        if (! isset($data[$property->getIdentifier()])) {
            return $content;
        }

        try {
            $value = \json_decode(
                $data[$property->getIdentifier()],
                TRUE,
                flags: JSON_THROW_ON_ERROR
            );
        } catch (\Throwable) {
            return $content;
        }

        if (! \is_array($value)) {
            return $content;
        }

        foreach ($value as $k => $v) {
            foreach ($content->getType()->getProperties() as $relationProperty) {
                if ($k !== $relationProperty->getIdentifier()) {
                    continue;
                }

                if ($relationProperty->hasRelation()) {
                    $relationProperty->setValue($this->hydrateRelation($relationProperty, $data));
                } else {
                    $relationProperty->setValue($v);
                }
            }
        }

        return $content;
    }
}
