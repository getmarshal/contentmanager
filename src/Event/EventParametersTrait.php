<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Event;

trait EventParametersTrait
{
    private array $params = [];

    public function getParam(string $param, mixed $default = null): mixed
    {
        return $this->params[$param] ?? $default;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function hasParam(string $param): bool
    {
        return isset($this->params[$param]);
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
}
