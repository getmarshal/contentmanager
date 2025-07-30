<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Listener;

use Laminas\Validator\ValidatorPluginManager;
use Marshal\ContentManager\ContentManager;
use Marshal\ContentManager\ContentRepository;
use Psr\Container\ContainerInterface;

final class WriteContentListenerFactory
{
    public function __invoke(ContainerInterface $container): WriteContentListener
    {
        $contentRepository = $container->get(ContentRepository::class);
        if (! $contentRepository instanceof ContentRepository) {
            throw new \RuntimeException("Invalid content repository");
        }

        $contentManager = $container->get(ContentManager::class);
        if (! $contentManager instanceof ContentManager) {
            throw new \RuntimeException("Invalid content manager");
        }

        $validatorPluginManager = $container->get(ValidatorPluginManager::class);
        if (! $validatorPluginManager instanceof ValidatorPluginManager) {
            throw new \RuntimeException("Invalid ValidatorPluginManager");
        }

        return new WriteContentListener($contentRepository, $contentManager, $validatorPluginManager);
    }
}
