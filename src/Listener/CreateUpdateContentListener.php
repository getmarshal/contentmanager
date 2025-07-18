<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Listener;

use Laminas\Validator\ValidatorPluginManager;
use Marshal\ContentManager\ContentManager;
use Marshal\ContentManager\Event\CreateContentEvent;
use Marshal\ContentManager\Event\UpdateContentEvent;
use Marshal\ContentManager\InputFilter\ContentInputFilter;
use Marshal\Database\ConnectionFactory;
use Marshal\EventManager\EventListenerInterface;

class CreateUpdateContentListener implements EventListenerInterface
{
    public function __construct(
        private ConnectionFactory $connectionFactory,
        private ContentManager $contentManager,
        private ValidatorPluginManager $validatorPluginManager
    ) {
    }

    public function getListeners(): array
    {
        return [
            CreateContentEvent::class => ['listener' => [$this, 'onCreateContent']],
            UpdateContentEvent::class => ['listener' => [$this, 'onUpdateContent']],
        ];
    }

    public function onCreateContent(CreateContentEvent $event): void
    {
        $content = $this->contentManager->get($event->getContentIdentifier());
        $inputFilter = new ContentInputFilter($content);
        $inputFilter->setData($event->getData());
        if (! $inputFilter->isValid($event->getData())) {
            return;
        }

        // additional validators
        foreach ($content->getConfig()->getValidators() as $name => $options) {
            $validator = $this->validatorPluginManager->get($name, $options);
            if (! $validator->isValid($inputFilter->getValues())) {
                return;
            }
        }

        // get the connection
        $connection = $this->connectionFactory->getConnection(
            $content->getType()->getDatabase()
        );

        // hydrate the content with the validated input
        $content->hydrate($inputFilter->getValues(), $connection->getDatabasePlatform());

        $result = $connection->getRepository()->create($content->getType());
        if (! \is_numeric($result)) {
            return;
        }

        $content->getType()->getAutoIncrement()->setValue(\intval($result));
        $event->setContent($content);
    }

    public function onUpdateContent(UpdateContentEvent $event): void
    {
    }
}
