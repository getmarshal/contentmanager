<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Middleware;

use Psr\Container\ContainerInterface;

final class ContentAdministrationMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): ContentAdministrationMiddleware
    {
        return new ContentAdministrationMiddleware;
    }
}
