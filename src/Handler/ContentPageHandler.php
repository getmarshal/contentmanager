<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Handler;

use Fig\Http\Message\StatusCodeInterface;
use Marshal\Application\AppInterface;
use Marshal\Application\AppManager;
use Marshal\ContentManager\ContentManager;
use Marshal\ContentManager\Event\ReadCollectionEvent;
use Marshal\ContentManager\Event\ReadContentEvent;
use Marshal\ContentManager\Schema\Content;
use Marshal\EventManager\EventDispatcherAwareInterface;
use Marshal\EventManager\EventDispatcherAwareTrait;
use Marshal\Platform\Web\Render\TemplateManager;
use Marshal\Util\Helper\RequestHandlerTrait;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ContentPageHandler implements EventDispatcherAwareInterface, RequestHandlerInterface
{
    use EventDispatcherAwareTrait;
    use RequestHandlerTrait;

    public function __construct(
        private AppManager $appManager,
        private ContentManager $contentManager,
        private TemplateManager $templateManager
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $platform = $this->getPlatform($request);
        $routeResult = $this->getRouteResult($request);

        // get the requested app
        $app = $this->getRequestedApp($routeResult);
        if (! $app instanceof AppInterface) {
            return $platform->formatResponse($request, status: StatusCodeInterface::STATUS_NOT_FOUND);
        }

        // get the schema requested
        $content = $this->getRequestedSchema($routeResult);
        if (! $content instanceof Content) {
            return $platform->formatResponse($request, status: StatusCodeInterface::STATUS_NOT_FOUND);
        }

        // handle single item requests
        $props = \array_keys($content->getProperties());
        $hasPropQuery = false;
        foreach ($request->getQueryParams() as $key => $value) {
            if (\in_array($key, $props, true)) {
                $hasPropQuery = true;
                break;
            }
        }
        if (true === $hasPropQuery) {
            return $this->handleContentItem($request, $content);
        }

        // get the content prefix, if set
        if ($content->hasRoutePrefix() && \array_key_exists('schema', $routeResult->getMatchedParams())) {
            if ($routeResult->getMatchedParams()['schema'] === $content->getRoutePrefix()) {
                return $this->handleContentIndex($request, $content);
            }
        }

        return $platform->formatResponse($request, status: StatusCodeInterface::STATUS_NOT_FOUND);
    }

    private function getRequestedApp(RouteResult $routeResult): ?AppInterface
    {
        $app = $routeResult->getMatchedParams()['app'] ?? null;
        if (! \is_string($app) || empty($app)) {
            return null;
        }

        $appIdentifier = null;
        foreach ($this->appManager->getConfig() as $identifier => $config) {
            if (! isset($config['route_prefix'])) {
                continue;
            }

            if ($config['route_prefix'] !== $app) {
                continue;
            }

            $appIdentifier = $identifier;
            break;
        }

        if (null === $appIdentifier) {
            return null;
        }

        return $this->appManager->get($appIdentifier);
    }

    private function getRequestedSchema(RouteResult $routeResult): ?Content
    {
        $schema = $routeResult->getMatchedParams()['schema'] ?? null;
        if (! \is_string($schema) || empty($schema)) {
            return null;
        }

        $schemaIdentifier = null;
        foreach ($this->contentManager->getConfig() as $identifier => $config) {
            if (! isset($config['routing']['route_prefix'])) {
                continue;
            }

            if ($config['routing']['route_prefix'] !== $schema) {
                continue;
            }

            $schemaIdentifier = $identifier;
            break;
        }

        if (null === $schemaIdentifier) {
            return null;
        }

        return $this->contentManager->get($schemaIdentifier);
    }

    private function handleContentIndex(ServerRequestInterface $request, Content $content): ResponseInterface
    {
        $platform = $this->getPlatform($request);
        $event = new ReadCollectionEvent($content->getIdentifier(), $request->getQueryParams());
        $this->getEventDispatcher()->dispatch($event);
        
        $options = [];
        if ($content->hasCollectionTemplate()) {
            $options['template'] = $content->getCollectionTemplate();
        }

        return $platform->formatResponse($request, [
            'collection' => $event->getCollection(),
        ], options: $options);
    }

    private function handleContentItem(ServerRequestInterface $request, Content $content): ResponseInterface
    {
        $platform = $this->getPlatform($request);

        $templateName = $content->hasContentTemplate() ? $content->getContentTemplate() : "marshal::error-404";
        $template = $this->templateManager->get($templateName);

        $queryArgs = [];
        if ($template->hasQueryParams()) {
            foreach ($template->getQueryParams() as $name => $param) {
                foreach ($request->getQueryParams() as $key => $value) {
                    if ($key !== $name) {
                        continue;
                    }
                    $queryArgs[$param] = $value;
                }
            }
        } else {
            $queryArgs = [];
            foreach ($request->getQueryParams() as $key => $value) {
                if ($content->hasProperty($key)) {
                    $queryArgs[$key] = $value;
                }
            }
        }

        $event = new ReadContentEvent($content->getIdentifier(), $queryArgs);
        $this->getEventDispatcher()->dispatch($event);
        if (! $event->hasContent()) {
            return $platform->formatResponse($request, status: StatusCodeInterface::STATUS_NOT_FOUND);
        }

        $data = [$content->getRoutePrefix() => $event->getContent()];
        
        if ($template->hasCollectionQuery()) {
            foreach ($template->getCollectionQuery() as $name => $query) {
                if (! isset($data[$query['related_property']])) {
                    continue;
                }

                $collectionEvent = new ReadCollectionEvent($query['schema'], [
                    $query['related_property'] => $data[$query['related_property']],
                ]);
                $this->getEventDispatcher()->dispatch($collectionEvent);
                $data[$name] = $collectionEvent->getCollection();
            }
        }

        $options = ['template' => $templateName];
        return $platform->formatResponse($request, $data, options: $options);
    }
}
