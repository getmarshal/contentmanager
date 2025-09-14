<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Middleware;

use Psr\Container\ContainerInterface;

final class ContentAuthorizationMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): ContentAuthorizationMiddleware
    {
        return new ContentAuthorizationMiddleware;
    }
}
