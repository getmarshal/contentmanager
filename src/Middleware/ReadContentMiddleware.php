<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Middleware;

use Marshal\ContentManager\Event\ReadContentEvent;
use Marshal\EventManager\EventDispatcherAwareInterface;
use Marshal\EventManager\EventDispatcherAwareTrait;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ReadContentMiddleware implements EventDispatcherAwareInterface, MiddlewareInterface
{
    use EventDispatcherAwareTrait;

    public const string CONTENT_ATTRIBUTE = "marshal::content";

    private const array CONTENT_ROUTES = [];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // get the route result
        $routeResult = $request->getAttribute(RouteResult::class);
        if (! $routeResult instanceof RouteResult || $routeResult->isFailure()) {
            return $handler->handle($request);
        }

        $routeOptions = $routeResult->getMatchedRoute()->getOptions();
        if (! isset($routeOptions['content']) || ! \is_array($routeOptions['content'])) {
            return $handler->handle($request);
        }

        // fetch request data
        $data = [];
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

        // return the response
        return $handler->handle($request->withAttribute(self::CONTENT_ATTRIBUTE, $data));
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
