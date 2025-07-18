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
                Listener\CreateUpdateContentListener::class => [
                    Event\CreateContentEvent::class,
                    Event\UpdateContentEvent::class,
                ],
                Listener\DeleteContentListener::class => [
                    Event\DeleteContentEvent::class,
                ],
                Listener\ReadContentListener::class => [
                    Event\ReadContentEvent::class,
                    Event\ReadCollectionEvent::class,
                ],
            ],
        ];
    }

    private function getDependencies(): array
    {
        return [
            'delegators' => [
                Command\FetchContentCommand::class => [
                    \Marshal\EventManager\EventDispatcherDelegatorFactory::class,
                ],
            ],
            'factories' => [
                Command\FetchContentCommand::class => \Laminas\ServiceManager\Factory\InvokableFactory::class,
                Listener\CreateUpdateContentListener::class => Listener\CreateUpdateContentListenerFactory::class,
                Listener\ReadContentListener::class => Listener\ReadContentListenerFactory::class,
                ContentManager::class => ContentManagerFactory::class,
            ],
        ];
    }
}
