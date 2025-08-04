<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Schema;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type as DBALType;

final class Property
{
    private mixed $value;

    public function __construct(private string $identifier, private PropertyConfig $config)
    {
        $this->value = $this->config->getDefault();
    }

    public function isAutoIncrement(): bool
    {
        return $this->config->isAutoIncrement();
    }

    public function getComment(): string
    {
        return $this->config->getComment();
    }

    public function getConvertToPhpType(): bool
    {
        return $this->config->getConvertToPhpType();
    }

    public function getDatabaseType(): DBALType
    {
        return $this->config->getDatabaseType();
    }

    public function getDatabaseTypeName(): string
    {
        return $this->config->getDatabaseTypeName();
    }

    public function getDatabaseValue(AbstractPlatform $databasePlatform): mixed
    {
        if (! $this->hasRelation()) {
            return $this->getDatabaseType()->convertToDatabaseValue($this->value, $databasePlatform);
        }

        $relation = $this->getValue();
        if (! $relation instanceof Content) {
            if (
                \is_array($relation)
                && isset($relation[$this->getRelationColumn()])
                && \is_scalar($relation[$this->getRelationColumn()])
            ) {
                return $relation[$this->getRelationColumn()];
            }

            return $relation;
        }

        return $relation->getProperty($this->getRelationColumn())->getDatabaseValue($databasePlatform);
    }

    public function getDefaultValue(): mixed
    {
        return $this->config->getDefault();
    }

    public function getFilters(): array
    {
        return $this->config->getFilters();
    }

    public function getFixed(): bool
    {
        return $this->config->getFixed();
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getIndex(): PropertyIndex
    {
        return $this->config->getIndex();
    }

    public function getLength(): ?int
    {
        return $this->config->getLength();
    }

    public function getNotNull(): bool
    {
        return $this->config->getNotNull();
    }

    public function getPlatformOptions(): array
    {
        return $this->config->getPlatformOptions();
    }

    public function getPrecision(): int
    {
        return $this->config->getPrecision();
    }

    public function getRelation(): PropertyRelation
    {
        if (! $this->hasRelation()) {
            throw new \InvalidArgumentException("Property {$this->getIdentifier()} has no relation");
        }

        return $this->config->getRelation();
    }

    public function getRelationProperty(): Property
    {
        return $this->getRelation()->getProperty();
    }

    public function getRelationColumn(): string
    {
        return $this->getRelationProperty()->getIdentifier();
    }

    public function getScale(): int
    {
        return $this->config->getScale();
    }

    public function getUniqueConstraint(): PropertyConstraint
    {
        return $this->config->getUniqueConstraint();
    }

    public function getUnsigned(): bool
    {
        return $this->config->getUnsigned();
    }

    public function getValidators(): array
    {
        return $this->config->getValidators();
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function hasComment(): bool
    {
        return $this->config->hasComment();
    }

    public function hasIndex(): bool
    {
        return $this->config->hasIndex();
    }

    public function hasRelation(): bool
    {
        return $this->config->hasRelation();
    }

    public function hasUniqueConstraint(): bool
    {
        return $this->config->hasUniqueConstraint();
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }
}
