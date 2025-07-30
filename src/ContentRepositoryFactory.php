<?php

declare(strict_types=1);

namespace Marshal\ContentManager;

use Marshal\Util\Database\ConnectionFactory;
use Psr\Container\ContainerInterface;

final class ContentRepositoryFactory
{
    public function __invoke(ContainerInterface $container): ContentRepository
    {
        $config = $container->get('config') ?? [];
        if (! \is_array($config)) {
            throw new \RuntimeException("Config not an array");
        }

        $connectionFactory = new ConnectionFactory($config['database'] ?? []);
        $contentManager = $container->get(ContentManager::class);
        \assert($contentManager instanceof ContentManager);

        return new ContentRepository($connectionFactory, $contentManager);
    }
}
