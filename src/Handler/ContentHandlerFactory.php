<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Handler;

use Psr\Container\ContainerInterface;

final class ContentHandlerFactory
{
    public function __invoke(ContainerInterface $container): ContentHandler
    {
        return new ContentHandler();
    }
}
