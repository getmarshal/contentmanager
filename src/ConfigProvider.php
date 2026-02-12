<?php

declare(strict_types=1);

namespace Marshal\ContentManager;

use Laminas\ConfigAggregator\ArrayProvider;
use Laminas\ConfigAggregator\ConfigAggregator;

class ConfigProvider
{
    public function __invoke(): array
    {
        $aggregator = new ConfigAggregator([
            Config\ContentSchema::class,
            new ArrayProvider([
                "dependencies" => $this->getDependencies(),
                "navigation" => ["paths" => $this->getRoutes()],
                "templates" => $this->getTemplatesConfig(),
            ]),
        ]);

        return $aggregator->getMergedConfig();
    }

    private function getDependencies(): array
    {
        return [
            "factories" => [
                Handler\ContentHandler::class => Handler\ContentHandlerFactory::class,
            ],
        ];
    }

    private function getRoutes(): array
    {
        return [
            "/content" => [
                "methods" => ["GET"],
                "middleware" => Handler\ContentHandler::class,
                "name" => Handler\ContentHandler::CONTENT_DASHBOARD,
                "options" => [
                    "template" => "content:dashboard",
                ],
            ],
            "/content/{schema}" => [
                "methods" => ["GET"],
                "middleware" => Handler\ContentHandler::class,
                "name" => Handler\ContentHandler::CONTENT_SCHEMA,
                "options" => [
                    "template" => "content:schema",
                ],
            ],
            "/content/{schema}/{type}" => [
                "methods" => ["GET"],
                "middleware" => Handler\ContentHandler::class,
                "name" => Handler\ContentHandler::CONTENT_SCHEMA_TYPE,
                "options" => [
                    "template" => "content:schema-type",
                ],
            ],
            "/content/{schema}/{type}/{item}" => [
                "methods" => ["GET"],
                "middleware" => Handler\ContentHandler::class,
                "name" => Handler\ContentHandler::CONTENT_SCHEMA_TYPE_ITEM,
                "options" => [
                    "template" => "content:schema-type-item",
                ],
            ],
        ];
    }

    private function getTemplatesConfig(): array
    {
        return [
            "content:dashboard" => [
                "filename" => __DIR__ . '/../templates/dashboard.twig'
            ],
            "content:schema" => [
                "filename" => __DIR__ . '/../templates/schema.twig'
            ],
            "content:schema-type" => [
                "filename" => __DIR__ . '/../templates/schema_type.twig'
            ],
            "content:schema-type-item" => [
                "filename" => __DIR__ . '/../templates/schema_type_item.twig'
            ],
        ];
    }
}
