<?php

declare(strict_types=1);

namespace Marshal\ContentManager;

class ConfigProvider
{
    public const string CONTENT_LOGGER = "marshal::content";

    public function __invoke(): array
    {
        return [
            'commands' => $this->getCommands(),
            'dependencies' => $this->getDependencies(),
            'events' => $this->getEventsConfig(),
            'loggers' => $this->getLoggers(),
            'schema' => $this->getSchemaConfig(),
        ];
    }

    private function getCommands(): array
    {
        return [
            Command\FetchContentCommand::NAME => Command\FetchContentCommand::class,
            'migration:generate' => Migration\MigrationGenerateCommand::class,
            'migration:rollback' => Migration\MigrationRollBackCommand::class,
            'migration:run' => Migration\MigrationRunCommand::class,
            'migration:setup' => Migration\MigrationSetupCommand::class,
            'migration:status' => Migration\MigrationStatusCommand::class,
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
                ],
                Listener\WriteContentListener::class => [
                    \Marshal\Util\Logger\LoggerFactoryDelegator::class,
                ],
                Middleware\ReadContentMiddleware::class => [
                    \Marshal\EventManager\EventDispatcherDelegatorFactory::class,
                ],
                Migration\MigrationGenerateCommand::class => [
                    \Marshal\EventManager\EventDispatcherDelegatorFactory::class,
                    \Marshal\Util\Database\DatabaseAwareDelegatorFactory::class,
                ],
                Migration\MigrationRollBackCommand::class => [
                    \Marshal\EventManager\EventDispatcherDelegatorFactory::class,
                ],
                Migration\MigrationRunCommand::class => [
                    \Marshal\EventManager\EventDispatcherDelegatorFactory::class,
                    \Marshal\Util\Database\DatabaseAwareDelegatorFactory::class,
                ],
                Migration\MigrationSetupCommand::class => [
                    \Marshal\EventManager\EventDispatcherDelegatorFactory::class,
                    \Marshal\Util\Database\DatabaseAwareDelegatorFactory::class,
                ],
                Migration\MigrationStatusCommand::class => [
                    \Marshal\EventManager\EventDispatcherDelegatorFactory::class,
                ],
            ],
            'factories' => [
                Command\FetchContentCommand::class => \Laminas\ServiceManager\Factory\InvokableFactory::class,
                ContentManager::class => ContentManagerFactory::class,
                ContentRepository::class => ContentRepositoryFactory::class,
                Listener\ReadContentListener::class => Listener\ReadContentListenerFactory::class,
                Listener\WriteContentListener::class => Listener\WriteContentListenerFactory::class,
                Middleware\ReadContentMiddleware::class => Middleware\ReadContentMiddlewareFactory::class,
                Migration\MigrationGenerateCommand::class => Migration\MigrationCommandFactory::class,
                Migration\MigrationRollBackCommand::class => Migration\MigrationCommandFactory::class,
                Migration\MigrationRunCommand::class => Migration\MigrationCommandFactory::class,
                Migration\MigrationSetupCommand::class => Migration\MigrationCommandFactory::class,
                Migration\MigrationStatusCommand::class => Migration\MigrationCommandFactory::class,
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
                ],
                'processors' => [
                    \Monolog\Processor\PsrLogMessageProcessor::class => [],
                ],
            ],
        ];
    }

    private function getSchemaConfig(): array
    {
        return [
            "marshal::migration" => [
                "properties" => [
                    'id' => [
                        'type' => 'bigint',
                        'notnull' => true,
                        'autoincrement' => true,
                    ],
                    'name' => [
                        'type' => 'string',
                        'notnull' => true,
                        'index' => true,
                        'length' => 255,
                    ],
                    'db' => [
                        'type' => 'string',
                        'notnull' => true,
                        'index' => true,
                        'length' => 255,
                    ],
                    'diff' => [
                        'type' => 'blob',
                        'notnull' => true,
                        'convertToPhpType' => false,
                    ],
                    'status' => [
                        'type' => 'smallint',
                        'notnull' => true,
                        'default' => 0,
                        'index' => true,
                    ],
                    'createdat' => [
                        'type' => 'datetime',
                        'notnull' => true,
                    ],
                    'updatedat' => [
                        'type' => 'datetime',
                    ],
                ],
            ],
        ];
    }
}
