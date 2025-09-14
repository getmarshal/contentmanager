<?php

declare(strict_types= 1);

namespace Marshal\ContentManager;

final class ContentQuery
{
    private array $columns = [];
    private array $groupBy = [];
    private array $having = [];
    private ?int $limit = null;
    private int $offset = 0;
    private array $orderBy = [];
    private string $schema;
    private bool $toArray = false;
    private array $where = [];

    public function __construct(?string $schema = null)
    {
        if ($schema !== null) {
            $this->schema = $schema;
        }
    }

    public function columns(array $columns): static
    {
        $this->columns = $columns;
        return $this;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getGroupBy(): array
    {
        return $this->groupBy;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    public function getSchema(): string
    {
        return $this->schema;
    }

    public function getToArray(): bool
    {
        return $this->toArray;
    }

    public function getWhere(): array
    {
        return $this->where;
    }

    public function groupBy(string $groupBy): static
    {
        $this->groupBy[] = $groupBy;
        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    public function orderBy(string $column, string $direction = "asc"): static
    {
        $this->orderBy[$column] = $direction;
        return $this;
    }

    public function properties(array $properties): static
    {
        foreach ($properties as $key => $value) {
            $this->where[$key] = $value;
        }
        return $this;
    }

    public function property(string $property, mixed $value): static
    {
        $this->where[$property] = $value;
        return $this;
    }

    public function schema(string $schema) : static
    {
        $this->schema = $schema;
        return $this;
    }

    public function toArray(): static
    {
        $this->toArray = true;
        return $this;
    }

    public function where(string $column, mixed $value): static
    {
        $this->where[$column] = $value;
        return $this;
    }
}
