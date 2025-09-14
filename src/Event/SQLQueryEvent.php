<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Event;

final class SQLQueryEvent
{
    public function __construct(private string $sql, private array $params)
    {
    }

    public function getSqlQuery(): string
    {
        return $this->sql;
    }

    public function getParams(): array
    {
        return $this->params;
    }
}
