<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Event;

trait QueryParametersTrait
{
    private const string GROUP_BY = "__group_by";
    private const string LIMIT = "__limit";
    private const string OFFSET = "__offset";
    private const string ORDER_BY = "__order_by";
    private const string WHERE = "__where";

    public function getGroupBy(): array
    {
        return $this->getParam(self::GROUP_BY, []);
    }

    public function getLimit(): ?int
    {
        return $this->getParam(self::LIMIT, null);
    }

    public function getOffset(): int
    {
        return $this->getParam(self::OFFSET, 0);
    }

    public function getOrderBy(): array
    {
        return $this->getParam(self::ORDER_BY, []);
    }

    public function getWhere(): array
    {
        return $this->getParam(self::WHERE, []);
    }

    public function groupBy(string $argument): static
    {
        $groupBy = $this->getParam(self::GROUP_BY, []);
        $groupBy[] = $argument;

        $this->setParam(self::GROUP_BY, $groupBy);
        return $this;
    }
    public function limit(?int $limit = null): static
    {
        $this->setParam(self::LIMIT, $limit);
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->setParam(self::OFFSET, $offset);
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $orderBy = $this->getParam(self::ORDER_BY, []);
        $orderBy[$column] = $direction;

        $this->setParam(self::ORDER_BY, $orderBy);
        return $this;
    }

    public function where(string $argument, array $parameters = []): static
    {
        $where = $this->getParam(self::WHERE, []);
        $where[$argument] = $parameters;

        $this->setParam(self::WHERE, $where);
        return $this;
    }
}
