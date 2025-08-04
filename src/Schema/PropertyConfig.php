<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Schema;

use Doctrine\DBAL\Types\Type;

final class PropertyConfig
{
    private bool $autoIncrement = false;
    /**
     * @var array<PropertyConstraint>
     */
    private string $comment;
    private array $constraints = [];
    private bool $convertToPhpType = true;
    private mixed $default = null;
    private array $filters = [];
    private bool $fixed = false;
    private PropertyIndex $index;
    private ?int $length = null;
    private bool $notNull = false;
    private array $platformOptions = [];
    private int $precision = 10;
    private PropertyRelation $relation;
    private int $scale = 0;
    private bool $unsigned = false;
    private array $validators = [];

    public function __construct(private array $definition)
    {
        if (isset($definition['autoincrement'])) {
            $this->autoIncrement = \boolval($definition['autoincrement']);
        }

        if (isset($definition['platformOptions'])) {
            $this->platformOptions = (array) $definition['platformOptions'];
        }

        if (isset($definition['fixed'])) {
            $this->fixed = \boolval($definition['fixed']);
        }

        if (isset($definition['length']) && \is_int($definition['length'])) {
            $this->length = $definition['length'];
        }

        if (isset($definition['index']) && \is_array($definition['index'])) {
            $this->index = new PropertyIndex($definition['index']);
        }

        if (isset($definition['notnull'])) {
            $this->notNull = \boolval($definition['notnull']);
        }

        if (isset($definition['precision'])) {
            $this->precision = \intval($definition['precision']);
        }

        if (isset($definition['relation']) && $definition['relation'] instanceof PropertyRelation) {
            $this->relation = $definition['relation'];
        }

        if (isset($definition['scale'])) {
            $this->scale = \intval($definition['scale']);
        }

        if (isset($definition['unsigned'])) {
            $this->unsigned = \boolval($definition['unsigned']);
        }

        if (isset($definition['comment']) && \is_string($definition['comment'])) {
            $this->comment = $definition['comment'];
        }

        if (isset($definition['default'])) {
            $this->default = $definition['default'];
        }

        if (isset($definition['convertToPhpType']) && \is_bool($definition['convertToPhpType'])) {
            $this->convertToPhpType = $definition['convertToPhpType'];
        }

        // setup index
        if (isset($definition['index'])) {
            $this->index = new PropertyIndex($definition['index']);
        }

        // setup constraints
        if (isset($definition['constraints']) && \is_array($definition['constraints'])) {
            foreach ($definition['constraints'] as $type => $constraintDefinition) {
                $this->constraints[$type] = new PropertyConstraint($type, $constraintDefinition);
            }
        }

        // setup input filters
        foreach ($definition['filters'] ?? [] as $filter => $options) {
            $this->filters[$filter] = $options;
        }

        // setup validators
        foreach ($definition['validators'] ?? [] as $validator => $options) {
            $this->validators[$validator] = $options;
        }
    }

    public function getConvertToPhpType(): bool
    {
        return $this->convertToPhpType;
    }

    public function getDatabaseType(): Type
    {
        return Type::getType($this->definition['type']);
    }

    public function getDatabaseTypeName(): string
    {
        return $this->definition['type'];
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function getPlatformOptions(): array
    {
        return $this->platformOptions;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getFixed(): bool
    {
        return $this->fixed;
    }

    public function getIndex(): PropertyIndex
    {
        return $this->index;
    }

    public function getLength(): ?int
    {
        return $this->length;
    }

    public function getNotNull(): bool
    {
        return $this->notNull;
    }

    public function getPrecision(): int
    {
        return $this->precision;
    }

    public function getRelation(): PropertyRelation
    {
        if (! $this->hasRelation()) {
            throw new \InvalidArgumentException("Property has no relation");
        }

        return $this->relation;
    }

    public function getScale(): int
    {
        return $this->scale;
    }

    public function getUnsigned(): bool
    {
        return $this->unsigned;
    }

    public function getUniqueConstraint(): PropertyConstraint
    {
        return $this->constraints['unique'];
    }

    public function getValidators(): array
    {
        return $this->validators;
    }

    public function hasComment(): bool
    {
        return isset($this->comment);
    }

    public function hasIndex(): bool
    {
        return isset($this->index);
    }

    public function hasRelation(): bool
    {
        return isset($this->relation);
    }

    public function hasUniqueConstraint(): bool
    {
        return isset($this->constraints['unique']) && $this->constraints['unique'] instanceof PropertyConstraint;
    }

    public function isAutoIncrement(): bool
    {
        return $this->autoIncrement;
    }
}
