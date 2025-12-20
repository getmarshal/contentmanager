<?php

declare(strict_types=1);

namespace Marshal\ContentManager;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Marshal\ContentManager\Schema\Property;
use Marshal\ContentManager\Schema\Type;

final class Content
{
    private bool $isEmpty = true;
    private array $additionalProperties = [];

    public function __construct(private readonly Type $type)
    {
    }

    public function addType(Type $type): static
    {
        foreach ($type->getProperties() as $property) {
            $this->additionalProperties[$property->getName()] = $property;
        }

        return $this;
    }

    public function get(string $property): mixed
    {
        return $this->getProperty($property)->getValue();
    }

    public function getAutoId(): int
    {
        return \intval($this->type->getAutoIncrement()->getValue());
    }

    public function getAutoIncrement(): Property
    {
        return $this->type->getAutoIncrement();
    }

    public function getDatabase(): string
    {
        return $this->type->getDatabase();
    }

    public function getTable(): string
    {
        return $this->type->getTable();
    }

    public function getTypeIdentifier(): string
    {
        return $this->type->getIdentifier();
    }

    /**
     * @return \Marshal\ContentManager\Schema\Property[]
     */
    public function getProperties(): array
    {
        return $this->type->getProperties();
    }

    public function getProperty(string $property): Property
    {
        return $this->type->getProperty($property);
    }

    public function getPropertyByIdentifier(string $identifier): Property
    {
        return $this->type->getPropertyByIdentifier($identifier);
    }

    public function getValidators(): array
    {
        return $this->type->getValidators();
    }

    public function hasProperty(string $name): bool
    {
        return $this->type->hasProperty($name);
    }

    public function hydrate(array $result, ?AbstractPlatform $databasePlatform = NULL, ?string $alias = null): static
    {
        $this->isEmpty = empty($result);
        $data = $this->normalizeData($result);
        foreach ($data as $key => $values) {
            if ($key === $this->getTable() || NULL !== $alias && $key === $alias) {
                foreach ($values as $name => $value) {
                    if (! $this->hasProperty($name)) {
                        continue;
                    }

                    $property = $this->getProperty($name);
                    if ($property->hasRelation()) {
                        if (\is_array($value)) {
                            $propertyData = [];
                            $relationContentTable = $property->getRelation()->getTable();
                            foreach ($value as $k => $v) {
                                $propertyData["{$relationContentTable}__$k"] = $v;
                            }
                            $property->getRelation()->getRelationContent()->hydrate($propertyData);
                        } elseif (\is_int($value)) {
                            $property->getRelation()->getRelationContent()->getAutoIncrement()->setValue($value);
                        } elseif ($value instanceof self) {
                            $property->getRelation()->setRelationContent($value);
                        }
                        continue;
                    }

                    // set property value
                    NULL === $databasePlatform || TRUE !== $property->getConvertToPhpType()
                        ? $property->setValue($value)
                        : $property->setValue(
                            $property->getDatabaseType()->convertToPHPValue($value, $databasePlatform)
                        );
                }
            } else {
                if (! $this->hasProperty($key)) {
                    continue;
                }

                $property = $this->getProperty($key);
                if (! $property->hasRelation()) {
                    continue;
                }

                $property->getRelation()->getRelationContent()->hydrate(
                    $result,
                    $databasePlatform,
                    $property->getRelation()->getAlias()
                );
            }
        }

        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->isEmpty;
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

    private function normalizeData(array $result): array
    {
        $data = [];
        foreach ($result as $key => $value) {
            $parts = \explode('__', $key);
            $name = \array_shift($parts);
            $data[$name][\implode('__', $parts)] = $value;
        }

        return $data;
    }
}
