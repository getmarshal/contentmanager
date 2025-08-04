<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Middleware;

use Marshal\ContentManager\ContentManager;
use Marshal\ContentManager\Event\ReadCollectionEvent;
use Marshal\ContentManager\Event\ReadContentEvent;
use Marshal\EventManager\EventDispatcherAwareInterface;
use Marshal\EventManager\EventDispatcherAwareTrait;
use Marshal\Platform\PlatformInterface;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ReadContentMiddleware implements EventDispatcherAwareInterface, MiddlewareInterface
{
    use EventDispatcherAwareTrait;

    public function __construct(private ContentManager $contentManager)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // get the route result
        $routeResult = $request->getAttribute(RouteResult::class);
        if (! $routeResult instanceof RouteResult) {
            return $handler->handle($request);
        }

        if ($routeResult->isFailure()) {
            return $handler->handle($request);
        }

        // fetch request data
        $data = [];
        $requestQueryArgs = $request->getQueryParams();
        $routeOptions = $routeResult->getMatchedRoute()->getOptions();
        if (isset($routeOptions['content']) && \is_array($routeOptions['content'])) {
            foreach ($routeOptions['content'] as $key => $value) {
                if (
                    ! \is_array($value)
                    || ! isset($value['schema'])
                    || ! isset($value['urlArgs'])
                    || ! \is_array($value['urlArgs'])
                ) {
                    continue;
                }

                $queryArgs = $this->getQueryArgs($request, $value['urlArgs']);
                $event = new ReadContentEvent($value['schema'], $queryArgs);
                $this->getEventDispatcher()->dispatch($event);
                if ($event->hasContent()) {
                    $data[$key] = $event->getContent();
                }
            }
        }

        if (isset($routeOptions['collections']) && \is_array($routeOptions['collections'])) {
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
        }

        // return the response
        $platform = $request->getAttribute(PlatformInterface::class);
        \assert($platform instanceof PlatformInterface);

        return $platform->formatResponseNew($request, $data);
    }

    private function getQueryArgs(ServerRequestInterface $request, array $config): array
    {
        $queryArgs = [];
        foreach ($config as $name => $arg) {
            if (! $request->getAttribute($arg, null)) {
                continue;
            }

            $queryArgs[$name] = $request->getAttribute($arg);
        }

        return $queryArgs;
    }
}
