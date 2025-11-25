<?php

declare(strict_types= 1);

namespace Marshal\ContentManager\Handler;

use Marshal\Application\AppManager;
use Marshal\ContentManager\ContentManager;
use Marshal\Server\Platform\Web\Template\TemplateManager;
use Psr\Container\ContainerInterface;

final class ContentPageHandlerFactory
{
    public function __invoke(ContainerInterface $container): ContentPageHandler
    {
        $appManager = $container->get(AppManager::class);
        \assert($appManager instanceof AppManager);

        $contentManager = $container->get(ContentManager::class);
        \assert($contentManager instanceof ContentManager);

        $templateManager = $container->get(TemplateManager::class);
        \assert($templateManager instanceof TemplateManager);

        return new ContentPageHandler($appManager, $contentManager, $templateManager);
    }
}
