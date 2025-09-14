<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Middleware;

use Marshal\ContentManager\Event\ReadCollectionEvent;
use Marshal\EventManager\EventDispatcherAwareInterface;
use Marshal\EventManager\EventDispatcherAwareTrait;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ReadCollectionMiddleware implements EventDispatcherAwareInterface, MiddlewareInterface
{
    use EventDispatcherAwareTrait;

    public const string COLLECTION_ATTRIBUTE = "marshal::collection";

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // get the route result
        $routeResult = $request->getAttribute(RouteResult::class);
        if (! $routeResult instanceof RouteResult || $routeResult->isFailure()) {
            return $handler->handle($request);
        }

        $routeOptions = $routeResult->getMatchedRoute()->getOptions();
        if (! isset($routeOptions['collections']) || ! \is_array($routeOptions['collections'])) {
            return $handler->handle($request);
        }

        $data = $request->getAttribute(ReadContentMiddleware::CONTENT_ATTRIBUTE, []);
        $requestQueryArgs = $request->getQueryParams();
        foreach ($routeOptions['collections'] as $key => $value) {
            if (! \is_array($value) || ! isset($value['schema'])) {
                continue;
            }

            if (isset($value['queryArgs']) && \is_array($value['queryArgs'])) {
                $queryArgs = [];
                foreach ($value['queryArgs'] as $name => $arg) {
                    if (! isset($requestQueryArgs[$arg])) {
                        continue;
                    }

                    $queryArgs[$name] = $requestQueryArgs[$arg];
                }
                $event = new ReadCollectionEvent($value['schema'], $queryArgs);
                $this->getEventDispatcher()->dispatch($event);
                $data[$key] = $event->getCollection();
            }

            if (isset($value['contentArgs']) && \is_array($value['contentArgs'])) {
                $queryArgs = [];
                foreach ($value['contentArgs'] as $name => $arg) {
                    if (! \is_string($arg) || ! isset($data[$arg])) {
                        continue;
                    }

                    $queryArgs[$name] = $data[$arg];
                    $event = new ReadCollectionEvent($value['schema'], $queryArgs);
                    $this->getEventDispatcher()->dispatch($event);
                    $data[$key] = $event->getCollection();
                }
            }
        }

        return $handler->handle($request->withAttribute(self::COLLECTION_ATTRIBUTE, $data));
    }
}
