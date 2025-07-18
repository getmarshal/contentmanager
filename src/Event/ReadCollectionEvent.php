<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Event;

use Marshal\ContentManager\Content;

class ReadCollectionEvent
{
    private Content $result;
    private iterable $data = [];

    public function __construct(private string $contentIndentifier, private array $params)
    {
    }

    public function getContentIdentifier(): string
    {
        return $this->contentIndentifier;
    }

    public function getData(): iterable
    {
        return $this->data;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function hasResult(): bool
    {
        return empty($this->data);
    }

    public function setResult(iterable $data): void
    {
        $this->data = $data;
    }
}
