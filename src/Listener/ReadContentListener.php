<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Listener;

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
        $collection = $this->contentRepository->filter($event);
        $event->setData($collection);
    }

    public function onReadContent(ReadContentEvent $event): void
    {
        $this->contentRepository->get($event);
    }
}
