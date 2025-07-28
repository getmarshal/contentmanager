<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Listener;

use loophp\collection\Collection;
use Marshal\ContentManager\ContentManager;
use Marshal\ContentManager\Event\ReadCollectionEvent;
use Marshal\ContentManager\Event\ReadContentEvent;
use Marshal\Database\ConnectionFactory;
use Marshal\EventManager\EventListenerInterface;

class ReadContentListener implements EventListenerInterface
{
    public function __construct(private ConnectionFactory $connectionFactory, private ContentManager $contentManager)
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
        $content = $this->contentManager->get($event->getContentIdentifier());
        $connection = $this->connectionFactory->getConnection(
            $content->getType()->getDatabase()
        );

        $collection = $connection->getRepository()->filter(
            $content->getType(),
            $event->getParams()
        );

        foreach ($event->getWhere() as $expression => $parameters) {
            $collection->andWhere($expression);
            foreach ($parameters as $key => $value) {
                $collection->setParameter($key, $value);
            }
        }

        foreach ($event->getGroupBy() as $expression) {
            $collection->addGroupBy($expression);
        }

        foreach ($event->getOrderBy() as $column => $direction) {
            $collection->addOrderBy($column, $direction);
        }

        $collection->setFirstResult($event->getOffset())
            ->setMaxResults($event->getLimit());

        $iterable = $collection->executeQuery()->iterateAssociative();
        $platform = $connection->getDatabasePlatform();
        $result = Collection::fromCallable(static function () use ($iterable, $event, $content, $platform): \Generator {
            foreach ($iterable as $row) {
                yield $event->getToArray()
                    ? $content->hydrate($row, $platform)->toArray()
                    : $content->hydrate($row, $platform);
            }
        });
        $event->setData($result);
    }

    public function onReadContent(ReadContentEvent $event): void
    {
        $content = $this->contentManager->get($event->getContentIdentifier());
        $connection = $this->connectionFactory->getConnection($content->getType()->getDatabase());
        $result = $connection->getRepository()->get($content->getType(), $event->getParams());
        if (! empty($result)) {
            $event->setRawResult($result);
            $event->setContent($content->hydrate($result, $connection->getDatabasePlatform()));
        }
    }
}
