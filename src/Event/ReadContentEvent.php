<?php

/**
 *
 */

declare(strict_types=1);

namespace Marshal\ContentManager\Event;

use Marshal\ContentManager\Content;

class ReadContentEvent
{
    private Content $result;

    public function __construct(private string $contentIndentifier, private array $params)
    {
    }

    public function getContentIdentifier(): string
    {
        return $this->contentIndentifier;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function hasResult(): bool
    {
        return isset($this->result);
    }

    public function getContent(): Content
    {
        if (! $this->hasResult()) {
            throw new \RuntimeException("Result not found");
        }

        return $this->result;
    }

    public function setContent(Content $content): void
    {
        $this->result = $content;
    }
}
