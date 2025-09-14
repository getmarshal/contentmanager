<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Listener;

use Marshal\ContentManager\ContentQuery;
use Marshal\ContentManager\ContentRepository;
use Marshal\ContentManager\Event\ReadCollectionEvent;
use Marshal\ContentManager\Event\ReadContentEvent;
use Marshal\EventManager\EventListenerInterface;

class ReadContentListener implements EventListenerInterface
{
    public function __construct(private ContentRepository $contentRepository)
    {
    }

    public function getListeners(): array
    {
        return [
            ReadContentEvent::class => ['listener' => [$this, 'onReadContent']],
            ReadCollectionEvent::class => ['listener' => [$this, 'onReadCollection']],
        ];
    }

    public function onReadCollection(ReadCollectionEvent $event): void
    {
        $query = new ContentQuery($event->getContentIdentifier());
        $query->properties($event->getParams() + $event->getWhere());
        foreach ($event->getGroupBy() as $group) {
            $query->groupBy($group);
        }
        foreach ($event->getOrderBy() as $column => $direction) {
            $query->orderBy($column, $direction);
        }
        
        if (\is_int($event->getLimit())) {
            $query->limit($event->getLimit());
        }
        $query->offset($event->getOffset());
        if ($event->getToArray()) {
            $query->toArray();
        }
        $event->setCollection($this->contentRepository->filter($query));
    }

    public function onReadContent(ReadContentEvent $event): void
    {
        $query = new ContentQuery($event->getContentIdentifier());
        $query->properties($event->getParams() + $event->getWhere())->limit(1);

        foreach ($event->getGroupBy() as $group) {
            $query->groupBy($group);
        }

        $event->setContent($this->contentRepository->get($query));
    }
}
