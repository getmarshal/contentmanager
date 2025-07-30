<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Listener;

use Marshal\ContentManager\ContentRepository;
use Psr\Container\ContainerInterface;

class ReadContentListenerFactory
{
    public function __invoke(ContainerInterface $container): ReadContentListener
    {
        $contentRepository = $container->get(ContentRepository::class);
        if (! $contentRepository instanceof ContentRepository) {
            throw new \RuntimeException("Invalid content repository");
        }

        return new ReadContentListener($contentRepository);
    }
}
