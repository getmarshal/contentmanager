<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Handler;

use Fig\Http\Message\StatusCodeInterface;
use Marshal\Platform\PlatformInterface;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ContentHandler implements RequestHandlerInterface
{
    public const string CONTENT_DASHBOARD = "content:dashboard";
    public const string CONTENT_SCHEMA = "content:schema";
    public const string CONTENT_SCHEMA_TYPE = "content:schema-type";
    public const string CONTENT_SCHEMA_TYPE_ITEM = "content:schema-type-item";

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $platform = $request->getAttribute(PlatformInterface::class);
        \assert($platform instanceof PlatformInterface);

        $routeResult = $request->getAttribute(RouteResult::class);
        \assert($routeResult instanceof RouteResult);

        return match ($routeResult->getMatchedRouteName()) {
            self::CONTENT_DASHBOARD => $this->handleContentDashboard($request, $platform),
            self::CONTENT_SCHEMA => $this->handleContentSchema($request, $platform),
            self::CONTENT_SCHEMA_TYPE => $this->handleContentSchemaType($request, $platform),
            self::CONTENT_SCHEMA_TYPE_ITEM => $this->handleContentSchemaTypeItem($request, $platform),
            default => $platform->formatResponse($request, status: StatusCodeInterface::STATUS_NOT_FOUND)
        };
    }

    private function handleContentDashboard(ServerRequestInterface $request, PlatformInterface $platform): ResponseInterface
    {
        return $platform->formatResponse($request);
    }

    private function handleContentSchema(ServerRequestInterface $request, PlatformInterface $platform): ResponseInterface
    {
        $schema = $request->getAttribute('schema');
        return $platform->formatResponse($request);
    }

    private function handleContentSchemaType(ServerRequestInterface $request, PlatformInterface $platform): ResponseInterface
    {
        $schema = $request->getAttribute('schema');
        return $platform->formatResponse($request);
    }

    private function handleContentSchemaTypeItem(ServerRequestInterface $request, PlatformInterface $platform): ResponseInterface
    {
        $schema = $request->getAttribute('schema');
        return $platform->formatResponse($request);
    }
}
