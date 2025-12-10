<?php

/**
 *
 */

declare(strict_types=1);

namespace Marshal\ContentManager\Event;

use Marshal\ContentManager\Content;

class UpdateContentEvent
{
    use ErrorMessagesTrait;
    use EventParametersTrait;

    private bool $isSuccess = FALSE;

    public function __construct(private Content $content, array $params = [])
    {
        $this->setParams($params);
    }

    public function getContent(): Content
    {
        return $this->content;
    }

    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    public function setIsSuccess(bool $isSuccess): static
    {
        $this->isSuccess = $isSuccess;
        return $this;
    }
}
