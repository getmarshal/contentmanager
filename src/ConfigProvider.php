<?php

declare(strict_types=1);

namespace Marshal\ContentManager;

class ConfigProvider
{
    public const string CONTENT_LOGGER = "marshal::content";

    public function __invoke(): array
    {
        return [
            "apps" => $this->getAppsConfig(),
            "commands" => $this->getCommands(),
            "dependencies" => $this->getDependencies(),
            "events" => $this->getEventsConfig(),
            "loggers" => $this->getLoggers(),
        ];
    }

    private function getAppsConfig(): array
    {
        return [
            "marshal::content" => [
                "route_prefix" => "content",
                "routes" => [
                    "/{app}[/{schema}]" => [
                        "methods" => ["GET", "POST"],
                        "middleware" => [
                            Middleware\ContentAuthorizationMiddleware::class,
                            Handler\ContentPageHandler::class,
                        ],
                        "name" => "marshal::content-page",
                    ],
                ],
            ],
            "marshal::admin" => [
                "routes" => [
                    "/content" => [
                        "methods" => ["GET", "POST"],
                        "middleware" => [
                            Middleware\ContentAdministrationMiddleware::class,
                            Handler\ContentAdministrationHandler::class,
                        ],
                        "name" => "marshal::content-admin",
                        "options" => [
                            "template" => "marshal::content-admin-page",
                        ],
                    ],
                    "/content/migration" => [
                        "method" => ["GET"],
                        "middleware" => [
                            Middleware\ContentAdministrationMiddleware::class,
                            Middleware\ContentAuthorizationMiddleware::class,
                            Handler\ContentMigrationHandler::class,
                        ],
                        "name" => "marshal::content-migration-admin-page",
                        "options" => [],
                    ],
                ],
            ],
        ];
    }

    private function getCommands(): array
    {
        return [
            Command\FetchContentCommand::NAME => Command\FetchContentCommand::class,
        ];
    }

    private function getDependencies(): array
    {
        return [
            'delegators' => [
                Command\FetchContentCommand::class => [
                    \Marshal\EventManager\EventDispatcherDelegatorFactory::class,
                ],
                ContentRepository::class => [
                    \Marshal\Util\Database\DatabaseAwareDelegatorFactory::class,
                    \Marshal\EventManager\EventDispatcherDelegatorFactory::class,
                ],
                Handler\ContentPageHandler::class => [
                    \Marshal\EventManager\EventDispatcherDelegatorFactory::class,
                ],
                Listener\WriteContentListener::class => [
                    \Marshal\Util\Logger\LoggerFactoryDelegator::class,
                ],
                Middleware\ContentAuthorizationMiddleware::class => [
                    \Marshal\EventManager\EventDispatcherDelegatorFactory::class,
                ],
                Middleware\ReadCollectionMiddleware::class => [
                    \Marshal\EventManager\EventDispatcherDelegatorFactory::class,
                ],
                Middleware\ReadContentMiddleware::class => [
                    \Marshal\EventManager\EventDispatcherDelegatorFactory::class,
                ],
            ],
            'factories' => [
                Command\FetchContentCommand::class                      => \Laminas\ServiceManager\Factory\InvokableFactory::class,
                ContentManager::class                                   => ContentManagerFactory::class,
                ContentRepository::class                                => ContentRepositoryFactory::class,
                Handler\ContentPageHandler::class                       => Handler\ContentPageHandlerFactory::class,
                Handler\ContentMigrationHandler::class                  => Handler\ContentMigrationHandlerFactory::class,
                Handler\ContentAdministrationHandler::class             => Handler\ContentAdministrationHandlerFactory::class,
                Listener\ReadContentListener::class                     => Listener\ReadContentListenerFactory::class,
                Listener\WriteContentListener::class                    => Listener\WriteContentListenerFactory::class,
                Middleware\ContentAdministrationMiddleware::class       => Middleware\ContentAdministrationMiddlewareFactory::class,
                Middleware\ContentAuthorizationMiddleware::class        => Middleware\ContentAuthorizationMiddlewareFactory::class,
                Middleware\ReadCollectionMiddleware::class              => \Laminas\ServiceManager\Factory\InvokableFactory::class,
                Middleware\ReadContentMiddleware::class                 => \Laminas\ServiceManager\Factory\InvokableFactory::class,
            ],
        ];
    }

    private function getEventsConfig(): array
    {
        return [
            Listener\ReadContentListener::class => [
                Event\ReadContentEvent::class,
                Event\ReadCollectionEvent::class,
            ],
            Listener\WriteContentListener::class => [
                Event\CreateContentEvent::class,
                Event\DeleteCollectionEvent::class,
                Event\DeleteContentEvent::class,
                Event\UpdateContentEvent::class,
            ],
        ];
    }

    private function getLoggers(): array
    {
        return [
            self::CONTENT_LOGGER => [
                'handlers' => [
                    \Monolog\Handler\ErrorLogHandler::class => [],
                    \Marshal\Util\Logger\Handler\DatabaseHandler::class => [],
                ],
                'processors' => [
                    \Monolog\Processor\PsrLogMessageProcessor::class => [],
                ],
            ],
        ];
    }
}
