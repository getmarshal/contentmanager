<?php

/**
 *
 */

declare(strict_types=1);

namespace Marshal\ContentManager\Event;

use Marshal\ContentManager\Content;
use Marshal\EventManager\ErrorMessagesTrait;
use Marshal\EventManager\EventParametersTrait;

class CreateContentEvent
{
    use ErrorMessagesTrait;
    use EventParametersTrait;

    private Content $content;
    public string $logMessage;
    public bool $saveMeta = FALSE;

    public function __construct(private string $contentIdentifier, array $params)
    {
        $this->setParams($params);
        $this->logMessage = "Content created: $contentIdentifier";
    }

    public function getContent(): Content
    {
        return $this->content;
    }

    public function getContentIdentifier(): string
    {
        return $this->contentIdentifier;
    }

    public function getLogMessage(): string
    {
        return $this->logMessage;
    }

    public function getSaveMeta(): bool
    {
        return $this->saveMeta;
    }

    public function isSuccess(): bool
    {
        return isset($this->content);
    }

    public function saveMeta(): static
    {
        $this->saveMeta = TRUE;
        return $this;
    }

    public function setContent(Content $content): static
    {
        $this->content = $content;
        return $this;
    }
}
