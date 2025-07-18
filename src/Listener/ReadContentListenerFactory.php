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
        $contentManager = $container->get(ContentManager::class);
        if (! $contentManager instanceof ContentManager) {
            throw new \RuntimeException("Invalid content manager");
        }

        $connectionFactory = new ConnectionFactory($container->get('config')['database']);

        return new ReadContentListener($contentManager, $connectionFactory);
    }
}
