<?php

declare(strict_types=1);

namespace Marshal\ContentManager;

use Psr\Container\ContainerInterface;

final class ContentRepositoryFactory
{
    public function __invoke(ContainerInterface $container): ContentRepository
    {
        $contentManager = $container->get(ContentManager::class);
        \assert($contentManager instanceof ContentManager);

        return new ContentRepository($contentManager);
    }
}
