<?php

declare(strict_types=1);

namespace Marshal\ContentManager;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            "dependencies" => $this->getDependencies(),
            "events" => $this->getEventsConfig(),
        ];
    }

    private function getDependencies(): array
    {
        return [
            'delegators' => [
                ContentRepository::class => [
                    \Marshal\Utils\Database\DatabaseAwareDelegatorFactory::class,
                ],
            ],
            'factories' => [
                ContentManager::class                                   => ContentManagerFactory::class,
                ContentRepository::class                                => ContentRepositoryFactory::class,
                Listener\ReadContentListener::class                     => Listener\ReadContentListenerFactory::class,
                Listener\WriteContentListener::class                    => Listener\WriteContentListenerFactory::class,
            ],
        ];
    }

    private function getEventsConfig(): array
    {
        return [
            'listeners' => [
                Listener\ReadContentListener::class => [
                    Event\ReadContentEvent::class => [
                        'listener' => 'onReadContent',
                    ],
                    Event\ReadCollectionEvent::class => [
                        'listener' => 'onReadCollection',
                    ],
                ],
                Listener\WriteContentListener::class => [
                    Event\CreateContentEvent::class => [
                        'listener' => 'onCreateContent',
                    ],
                    Event\DeleteCollectionEvent::class => [
                        'listener' => 'onDeleteCollectionEvent',
                    ],
                    Event\DeleteContentEvent::class => [
                        'listener' => 'onDeleteContentEvent',
                    ],
                    Event\UpdateContentEvent::class => [
                        'listener' => 'onUpdateContent',
                    ],
                ],
            ],
        ];
    }
}
