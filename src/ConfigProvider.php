<?php

declare(strict_types=1);

namespace Marshal\ContentManager;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'commands' => [
                Command\FetchContentCommand::NAME => Command\FetchContentCommand::class,
            ],
            'dependencies' => $this->getDependencies(),
            'events' => [
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
            ],
            'loggers' => $this->getLoggers(),
        ];
    }

    private function getDependencies(): array
    {
        return [
            'delegators' => [
                Command\FetchContentCommand::class => [
                    \Marshal\EventManager\EventDispatcherDelegatorFactory::class,
                ],
                Listener\WriteContentListener::class => [
                    \Marshal\Logger\LoggerFactoryDelegator::class,
                ],
            ],
            'factories' => [
                Command\FetchContentCommand::class => \Laminas\ServiceManager\Factory\InvokableFactory::class,
                Listener\ReadContentListener::class => Listener\ReadContentListenerFactory::class,
                Listener\WriteContentListener::class => Listener\WriteContentListenerFactory::class,
                ContentManager::class => ContentManagerFactory::class,
            ],
        ];
    }

    private function getLoggers(): array
    {
        return [
            'marshal::content' => [
                'handlers' => [
                    \Monolog\Handler\ErrorLogHandler::class => [],
                ],
                'processors' => [
                    \Monolog\Processor\PsrLogMessageProcessor::class => [],
                ],
            ],
        ];
    }
}
