<?php

declare(strict_types=1);

namespace Marshal\ContentManager;

class ConfigProvider
{
    public const string CONTENT_LOGGER = "marshal::content";

    public function __invoke(): array
    {
        return [
            "commands" => $this->getCommands(),
            "dependencies" => $this->getDependencies(),
            "events" => $this->getEventsConfig(),
            "navigation" => $this->getNavigationConfig(),
            "loggers" => $this->getLoggers(),
        ];
    }

    private function getNavigationConfig(): array
    {
        return [
            "paths" => [
                "/content/{app}[/{schema}]" => [
                    "methods" => ["GET", "POST"],
                    "middleware" => [
                        Middleware\ContentAuthorizationMiddleware::class,
                        Handler\ContentPageHandler::class,
                    ],
                    "name" => "marshal::content-page",
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
