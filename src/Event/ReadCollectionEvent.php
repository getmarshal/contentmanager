<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Event;

use loophp\collection\Collection;

class ReadCollectionEvent
{
    use EventParametersTrait;
    use QueryParametersTrait;

    private Collection $collection;
    private bool $toArray = false;

    public function __construct(private string $contentIdentifier, array $params = [])
    {
        $this->collection = Collection::empty();
        $this->setParams($params);
    }

    public function getContentIdentifier(): string
    {
        return $this->contentIdentifier;
    }

    public function getCollection(): Collection
    {
        return $this->collection;
    }

    public function getToArray(): bool
    {
        return $this->toArray;
    }

    public function setCollection(Collection $collection): void
    {
        $this->collection = $collection;
    }

    public function toArray(): static
    {
        $this->toArray = true;
        return $this;
    }
}
