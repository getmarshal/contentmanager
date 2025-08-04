<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Schema;

use Doctrine\DBAL\Platforms\AbstractPlatform;

final class Content
{
    public function __construct(
        private string $database,
        private string $table,
        private array $config,
        private array $properties
    ) {
    }

    public function get(string $property, mixed $default = null): mixed
    {
        if (! $this->hasProperty($property)) {
            return $default;
        }

        return $this->getProperty($property)->getValue();
    }

    public function getAutoIncrement(): Property
    {
        foreach ($this->properties as $property) {
            if ($property->isAutoIncrement()) {
                return $property;
            }
        }

        throw new \InvalidArgumentException("no autoincrement property");
    }

    public function getDatabase(): string
    {
        return $this->database;
    }

    public function getIdentifier(): string
    {
        return "{$this->getDatabase()}::{$this->getTable()}";
    }

    /**
     * @return Property[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getProperty(string $property): Property
    {
        if (! $this->hasProperty($property)) {
            throw new \InvalidArgumentException(
                "Missing property $property on type {$this->getIdentifier()}"
            );
        }

        return $this->properties[$property];
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getValidators(): array
    {
        return $this->config['validators'] ?? [];
    }

    public function hasProperty(string $property): bool
    {
        return isset($this->properties[$property]);
    }

    public function hydrate(array $data, ?AbstractPlatform $databasePlatform = NULL): static
    {
        foreach ($data as $key => $value) {
            if (! $this->hasProperty($key)) {
                continue;
            }

            $property = $this->getProperty($key);
            if (! $property->hasRelation()) {
                NULL === $databasePlatform || TRUE !== $property->getConvertToPhpType()
                    ? $property->setValue($value)
                    : $property->setValue(
                        $property->getDatabaseType()->convertToPHPValue($value, $databasePlatform)
                    );
            } else {
                $property->setValue($this->hydrateRelation($property, $data));
            }
        }

        return $this;
    }

    public function toArray(): array
    {
        $values = [];
        foreach ($this->getProperties() as $property) {
            $value = $property->getValue();
            $values[$property->getIdentifier()] = $value instanceof self
                ? $value->toArray()
                : $value;
        }

        return $values;
    }

    private function hydrateRelation(Property $property, array $data, ?AbstractPlatform $databasePlatform = NULL): self
    {
        $content = $property->getRelation()->getSchema();
        if (! isset($data[$property->getIdentifier()])) {
            return $content;
        }

        if ($data[$property->getIdentifier()] instanceof self) {
            return $data[$property->getIdentifier()];
        }

        if (\is_int($data[$property->getIdentifier()])) {
            $content->getAutoIncrement()->setValue($data[$property->getIdentifier()]);
            return $content;
        }

        if (\is_array($data[$property->getIdentifier()])) {
            $value = $data[$property->getIdentifier()];
        } else {
            try {
                $value = \json_decode(
                    $data[$property->getIdentifier()],
                    TRUE,
                    flags: JSON_THROW_ON_ERROR
                );
            } catch (\Throwable) {
                return $content;
            }
        }

        if (! \is_array($value)) {
            return $content;
        }

        foreach ($value as $k => $v) {
            if (! $content->hasProperty($k)) {
                continue;
            }

            $relationProperty = $content->getProperty($k);
            if ($relationProperty->hasRelation()) {
                $relationProperty->setValue($this->hydrateRelation($relationProperty, $data, $databasePlatform));
            } else {
                NULL === $databasePlatform || TRUE !== $relationProperty->getConvertToPhpType()
                    ? $relationProperty->setValue($v)
                    : $relationProperty->setValue(
                        $relationProperty->getDatabaseType()->convertToPHPValue($v, $databasePlatform)
                    );
            }
        }

        return $content;
    }
}
