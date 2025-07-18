<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Listener;

use Marshal\ContentManager\ContentManager;
use Marshal\ContentManager\Event\ReadCollectionEvent;
use Marshal\ContentManager\Event\ReadContentEvent;
use Marshal\Database\ConnectionFactory;
use Marshal\EventManager\EventListenerInterface;

class ReadContentListener implements EventListenerInterface
{
    public function __construct(
        private ContentManager $contentManager,
        private ConnectionFactory $connectionFactory,
    ) {
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
        $content = $this->contentManager->get($event->getContentIdentifier());
        $connection = $this->connectionFactory->getConnection(
            $content->getType()->getDatabase()
        );

        $data = $connection->getRepository()->filter(
            $content->getType(),
            $event->getParams()
        )->executeQuery()->iterateAssociative();

        $event->setResult($data);
    }

    public function onReadContent(ReadContentEvent $event): void
    {
        $content = $this->contentManager->get($event->getContentIdentifier());
        $connection = $this->connectionFactory->getConnection(
            $content->getType()->getDatabase()
        );
        $result = $connection->getRepository()->get(
            $content->getType(),
            $event->getParams()
        );

        if (! empty($result)) {
            $content->hydrate($result, $connection->getDatabasePlatform());
            $event->setContent($content);
        }
    }
}
