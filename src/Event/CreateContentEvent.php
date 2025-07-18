<?php

/**
 *
 */

declare(strict_types=1);

namespace Marshal\ContentManager\Event;

use Marshal\ContentManager\Content;

class CreateContentEvent
{
    private Content $content;
    public bool $isCreated = false;
    public string $logMessage = "Content created";

    public function __construct(private string $contentIdentifier, private array $params, private bool $createMeta = TRUE)
    {
    }

    public function getCreateMeta(): bool
    {
        return $this->createMeta;
    }

    public function getContent(): Content
    {
        return $this->content;
    }

    public function getContentIdentifier(): string
    {
        return $this->contentIdentifier;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getLogMessage(): string
    {
        return $this->logMessage;
    }

    public function isFailure(): bool
    {
        return ! $this->isSuccess();
    }

    public function isSuccess(): bool
    {
        return isset($this->content);
    }

    public function setContent(Content $content): void
    {
        $this->content = $content;
    }
}
