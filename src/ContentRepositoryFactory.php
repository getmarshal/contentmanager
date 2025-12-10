<?php

declare(strict_types=1);

namespace Marshal\ContentManager;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

final class ContentRepositoryFactory
{
    public function __invoke(ContainerInterface $container): ContentRepository
    {
        $contentManager = $container->get(ContentManager::class);
        \assert($contentManager instanceof ContentManager);

        $eventDispatcher = $container->get(EventDispatcherInterface::class);
        \assert($eventDispatcher instanceof EventDispatcherInterface);

        return new ContentRepository($contentManager, $eventDispatcher);
    }
}
