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
            "validators" => $this->getValidatorsConfig(),
        ];
    }

    private function getDependencies(): array
    {
        return [
            "invokables" => [
                Listener\ReadContentListener::class                     => Listener\ReadContentListener::class,
            ],
            "factories" => [
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

    private function getValidatorsConfig(): array
    {
        return [
            "factories" => [
                Validator\PropertyConfigValidator::class => Validator\PropertyConfigValidatorFactory::class,
                Validator\TypeConfigValidator::class => Validator\TypeConfigValidatorFactory::class,
            ],
        ];
    }
}
