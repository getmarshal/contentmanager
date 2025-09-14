<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Handler;

use Psr\Container\ContainerInterface;

final class ContentMigrationHandlerFactory
{
    public function __invoke(ContainerInterface $container): ContentMigrationHandler
    {
        return new ContentMigrationHandler;
    }
}
