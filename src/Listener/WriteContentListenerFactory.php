<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Listener;

use Laminas\Validator\ValidatorPluginManager;
use Psr\Container\ContainerInterface;

final class WriteContentListenerFactory
{
    public function __invoke(ContainerInterface $container): WriteContentListener
    {
        $validatorPluginManager = $container->get(ValidatorPluginManager::class);
        if (! $validatorPluginManager instanceof ValidatorPluginManager) {
            throw new \RuntimeException("Invalid ValidatorPluginManager");
        }

        return new WriteContentListener($validatorPluginManager);
    }
}
