<?php

/**
 *
 */

declare(strict_types=1);

namespace Marshal\ContentManager\Event;

use Marshal\ContentManager\Content;
use Marshal\EventManager\EventParametersTrait;

class ReadContentEvent
{
    use EventParametersTrait;

    private Content $content;
    private array $rawResult = [];
    private array $where = [];

    public function __construct(private string $contentIdentifier, array $params = [])
    {
        $this->setParams($params);
    }

    public function getContentIdentifier(): string
    {
        return $this->contentIdentifier;
    }

    public function getRawResult(): array
    {
        return $this->rawResult;
    }

    public function hasContent(): bool
    {
        return isset($this->content);
    }

    public function getContent(): Content
    {
        return $this->content;
    }

    public function getWhere(): array
    {
        return $this->where;
    }

    public function setContent(Content $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function setRawResult(array $result): static
    {
        $this->rawResult = $result;
        return $this;
    }

    public function where(string $expression, array $parameters = []): static
    {
        $this->where[$expression] = $parameters;
        return $this;
    }
}
