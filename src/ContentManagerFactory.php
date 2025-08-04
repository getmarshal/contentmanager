<?php

declare(strict_types=1);

namespace Marshal\ContentManager;

use Psr\Container\ContainerInterface;

final class ContentManagerFactory
{
    public function __invoke(ContainerInterface $container): ContentManager
    {
        $config = $container->get('config')['schema'] ?? [];
        return new ContentManager($container, $config);
    }
}
