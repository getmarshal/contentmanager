<?php

/**
 *
 */

declare(strict_types=1);

namespace Marshal\ContentManager\Event;

use Marshal\ContentManager\Content;

class DeleteContentEvent
{
    private bool $isSuccess = FALSE;

    public function __construct(private Content $content)
    {
    }

    public function getContent(): Content
    {
        return $this->content;
    }

    public function getIsSuccess(): bool
    {
        return $this->isSuccess;
    }

    public function setIsSuccess(bool $isSuccess): static
    {
        $this->isSuccess = $isSuccess;
        return $this;
    }
}
