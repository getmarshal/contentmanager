<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Event;

use loophp\collection\Collection;

class ReadCollectionEvent
{
    private Collection $collection;
    private array $groupBy = [];
    private ?int $limit = NULL;
    private int $offset = 0;
    private bool $toArray = FALSE;
    private array $orderBy = [];
    private array $where = [];

    public function __construct(private string $contentIdentifier, private array $params = [])
    {
        $this->collection = Collection::empty();
    }

    public function getContentIdentifier(): string
    {
        return $this->contentIdentifier;
    }

    public function getCollection(): Collection
    {
        return $this->collection;
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

    public function getToArray(): bool
    {
        return $this->toArray;
    }

    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getWhere(): array
    {
        return $this->where;
    }

    public function groupBy(string $argument): static
    {
        $this->groupBy[] = $argument;
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

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->orderBy[$column] = $direction;
        return $this;
    }

    public function setParam(string $param, mixed $value): static
    {
        $this->params[$param] = $value;
        return $this;
    }

    public function setParams(array $params): static
    {
        $this->params = $params;
        return $this;
    }

    public function setData(Collection $collection): void
    {
        $this->collection = $collection;
    }

    public function toArray(): static
    {
        $this->toArray = TRUE;
        return $this;
    }

    public function where(string $argument, array $parameters = []): static
    {
        $this->where[$argument] = $parameters;
        return $this;
    }
}
