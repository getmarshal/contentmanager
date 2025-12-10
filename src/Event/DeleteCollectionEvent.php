<?php

/**
 *
 */

declare(strict_types=1);

namespace Marshal\ContentManager\Event;

class DeleteCollectionEvent
{
    use ErrorMessagesTrait;
    use EventParametersTrait;

    private int $deleteCount = 0;

    public function __construct(private string $contentIdentifier, array $params)
    {
        $this->setParams($params);
    }

    public function getContentIdentifier(): string
    {
        return $this->contentIdentifier;
    }

    public function getDeleteCount(): int
    {
        return $this->deleteCount;
    }

    public function setDeleteCount(int $deleteCount): static
    {
        $this->deleteCount = $deleteCount;
        return $this;
    }
}
