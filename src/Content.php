<?php

declare(strict_types=1);

namespace Marshal\ContentManager;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Marshal\Utils\Database\Schema\Property;
use Marshal\Utils\Database\Schema\Type;

final class Content
{
    private array $data = [];
    private array $properties = [];

    public function __construct(private Type $type)
    {
        foreach ($type->getProperties() as $property) {
            $this->properties[$property->getName()] = $property;
        }
    }

    public function addType(Type $type): static
    {
        foreach ($type->getProperties() as $property) {
            if ($this->hasProperty($property->getName())) {
                continue;
            }

            $this->properties[$property->getName()] = $property;
        }

        return $this;
    }

    public function get(string $property): mixed
    {
        if (! $this->hasProperty($property)) {
            throw new \InvalidArgumentException("Property $property does not exist");
        }

        return $this->getProperty($property)->getValue();
    }

    public function getAutoId(): int
    {
        return \intval($this->getType()->getAutoIncrement()->getValue());
    }

    public function getTable(): string
    {
        return $this->type->getTable();
    }

    /**
     * @return \Marshal\Utils\Database\Schema\Property[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getProperty(string $property): Property
    {
        if (! $this->hasProperty($property)) {
            throw new \InvalidArgumentException(
                "Missing property $property on content type {$this->type->getName()}"
            );
        }

        return $this->properties[$property];
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function hasProperty(string $name): bool
    {
        return isset($this->properties[$name]);
    }

    public function hydrate(array $data, ?AbstractPlatform $databasePlatform = NULL): static
    {
        $this->data = $data;
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

    public function isEmpty(): bool
    {
        return empty($this->data);
    }

    public function toArray(): array
    {
        $values = [];
        foreach ($this->getProperties() as $property) {
            $value = $property->getValue();
            $values[$property->getName()] = $value instanceof self
                ? $value->toArray()
                : $value;
        }

        return $values;
    }

    private function hydrateRelation(Property $property, array $data, ?AbstractPlatform $databasePlatform = NULL): self
    {
        $content = new self($property->getRelation()->getType());
        if (! isset($data[$property->getName()])) {
            return $content;
        }

        if ($data[$property->getName()] instanceof self) {
            return $data[$property->getName()];
        }

        if (\is_int($data[$property->getName()])) {
            $content->getType()->getAutoIncrement()->setValue($data[$property->getName()]);
            return $content;
        }

        if (\is_array($data[$property->getName()])) {
            $value = $data[$property->getName()];
        } else {
            try {
                $value = \json_decode(
                    $data[$property->getName()],
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
