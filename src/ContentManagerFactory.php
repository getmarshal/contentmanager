<?php

declare(strict_types=1);

namespace Marshal\ContentManager;

use Marshal\Utils\Database\Schema\SchemaManager;
use Psr\Container\ContainerInterface;

final class ContentManagerFactory
{
    public function __invoke(ContainerInterface $container): ContentManager
    {
        $schemaManager = $container->get(SchemaManager::class);
        if (! $schemaManager instanceof SchemaManager) {
            throw new \InvalidArgumentException(
                \sprintf("Expected %s, %s given instead", SchemaManager::class, \get_debug_type($schemaManager))
            );
        }

        return new ContentManager($schemaManager);
    }
}
