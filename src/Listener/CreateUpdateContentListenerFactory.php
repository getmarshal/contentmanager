<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Listener;

use Laminas\Validator\ValidatorPluginManager;
use Marshal\ContentManager\ContentManager;
use Marshal\Database\ConnectionFactory;
use Psr\Container\ContainerInterface;

final class CreateUpdateContentListenerFactory
{
    public function __invoke(ContainerInterface $container): CreateUpdateContentListener
    {
        $contentManager = $container->get(ContentManager::class);
        if (! $contentManager instanceof ContentManager) {
            throw new \RuntimeException("Invalid content manager");
        }

        $connectionFactory = new ConnectionFactory($container->get('config')['database']);
        $validatorPluginManager = $container->get(ValidatorPluginManager::class);
        if (! $validatorPluginManager instanceof ValidatorPluginManager) {
            throw new \RuntimeException("Invalid ValidatorPluginManager");
        }

        return new CreateUpdateContentListener($connectionFactory, $contentManager, $validatorPluginManager);
    }
}
