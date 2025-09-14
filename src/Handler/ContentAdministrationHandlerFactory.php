<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Handler;

use Psr\Container\ContainerInterface;

final class ContentAdministrationHandlerFactory
{
    public function __invoke(ContainerInterface $container): ContentAdministrationHandler
    {
        return new ContentAdministrationHandler;
    }
}
