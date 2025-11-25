<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Handler;

use Fig\Http\Message\StatusCodeInterface;
use Marshal\Application\AppManager;
use Marshal\ContentManager\ContentManager;
use Marshal\ContentManager\Event\ReadCollectionEvent;
use Marshal\ContentManager\Event\ReadContentEvent;
use Marshal\ContentManager\Content;
use Marshal\EventManager\EventDispatcherAwareInterface;
use Marshal\EventManager\EventDispatcherAwareTrait;
use Marshal\Server\Platform\Web\Template\TemplateManager;
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
        if ($content->getType()->hasRoutePrefix() && \array_key_exists('schema', $routeResult->getMatchedParams())) {
            if ($routeResult->getMatchedParams()['schema'] === $content->getType()->getRoutePrefix()) {
                return $this->handleContentIndex($request, $content);
            }
        }

        return $platform->formatResponse($request, status: StatusCodeInterface::STATUS_NOT_FOUND);
    }

    private function getRequestedSchema(RouteResult $routeResult): ?Content
    {
        $schema = $routeResult->getMatchedParams()['schema'] ?? null;
        if (! \is_string($schema) || empty($schema)) {
            return null;
        }

        foreach ($this->contentManager->getAll() as $content) {
            if (! $content->getType()->hasRoutePrefix()) {
                continue;
            }

            if ($content->getType()->getRoutePrefix() !== $schema) {
                continue;
            }

            return $content;
        }

        return null;
    }

    private function handleContentIndex(ServerRequestInterface $request, Content $content): ResponseInterface
    {
        $platform = $this->getPlatform($request);
        $event = new ReadCollectionEvent($content->getType()->getIdentifier(), $request->getQueryParams());
        $this->getEventDispatcher()->dispatch($event);
        
        $options = [];
        if ($content->getType()->hasCollectionTemplate()) {
            $options['template'] = $content->getType()->getCollectionTemplate();
        }

        return $platform->formatResponse($request, [
            'collection' => $event->getCollection(),
        ], options: $options);
    }

    private function handleContentItem(ServerRequestInterface $request, Content $content): ResponseInterface
    {
        $platform = $this->getPlatform($request);

        $templateName = $content->getType()->hasContentTemplate() ? $content->getType()->getContentTemplate() : "marshal::error-404";
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

        $event = new ReadContentEvent($content->getType()->getIdentifier(), $queryArgs);
        $this->getEventDispatcher()->dispatch($event);
        if (! $event->hasContent()) {
            return $platform->formatResponse($request, status: StatusCodeInterface::STATUS_NOT_FOUND);
        }

        $data = [$content->getType()->getRoutePrefix() => $event->getContent()];
        
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
