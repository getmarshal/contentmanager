<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Middleware;

use Marshal\ContentManager\ContentManager;
use Psr\Container\ContainerInterface;

final class ReadContentMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): ReadContentMiddleware
    {
        $contentManager = $container->get(ContentManager::class);
        \assert($contentManager instanceof ContentManager);

        return new ReadContentMiddleware($contentManager);
    }
}
