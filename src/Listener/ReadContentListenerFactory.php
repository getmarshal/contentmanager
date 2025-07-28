<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Listener;

use Marshal\ContentManager\ContentManager;
use Marshal\Database\ConnectionFactory;
use Psr\Container\ContainerInterface;

class ReadContentListenerFactory
{
    public function __invoke(ContainerInterface $container): ReadContentListener
    {
        $connectionFactory = new ConnectionFactory($container->get('config')['database']);
        $contentManager = $container->get(ContentManager::class);
        if (! $contentManager instanceof ContentManager) {
            throw new \RuntimeException("Invalid ContentManager");
        }

        return new ReadContentListener($connectionFactory, $contentManager);
    }
}
