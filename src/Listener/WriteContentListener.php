<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Listener;

use Laminas\Validator\ValidatorPluginManager;
use Marshal\ContentManager\ContentManager;
use Marshal\ContentManager\Event\CreateContentEvent;
use Marshal\ContentManager\Event\DeleteCollectionEvent;
use Marshal\ContentManager\Event\DeleteContentEvent;
use Marshal\ContentManager\Event\UpdateContentEvent;
use Marshal\ContentManager\InputFilter\ContentInputFilter;
use Marshal\Database\ConnectionFactory;
use Marshal\EventManager\EventListenerInterface;
use Marshal\Logger\LoggerFactoryAwareInterface;
use Marshal\Logger\LoggerFactoryTrait;

class WriteContentListener implements EventListenerInterface, LoggerFactoryAwareInterface
{
    use LoggerFactoryTrait;

    private const string CONTENT_LOGGER = 'marshal::content';

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
            DeleteCollectionEvent::class => ['listener' => [$this, 'onDeleteCollectionEvent']],
            DeleteContentEvent::class => ['listener' => [$this, 'onDeleteContent']],
        ];
    }

    public function onCreateContent(CreateContentEvent $event): void
    {
        $content = $this->contentManager->get($event->getContentIdentifier());

        $inputFilter = new ContentInputFilter($content);
        $inputFilter->setData($event->getParams());
        if (! $inputFilter->isValid()) {
            $event->setErrorMessages($inputFilter->getMessages());
            $this->getLogger(self::CONTENT_LOGGER)->info("Invalid content input", $inputFilter->getMessages());
            return;
        }

        // additional validators
        foreach ($content->getConfig()->getValidators() as $name => $options) {
            $validator = $this->validatorPluginManager->get($name, $options);
            if (! $validator->isValid($inputFilter->getValues())) {
                $event->setErrorMessages($validator->getMessages());
                $this->getLogger(self::CONTENT_LOGGER)->info("Invalid content input", $validator->getMessages());
                return;
            }
        }

        // get the connection
        $connection = $this->connectionFactory->getConnection(
            $content->getType()->getDatabase()
        );

        // hydrate the content with the validated input
        $content->hydrate($inputFilter->getValues());

        $result = $connection->getRepository()->create($content->getType());
        if (! \is_numeric($result)) {
            $event->setErrorMessage('create', "Error saving content");
            $this->getLogger(self::CONTENT_LOGGER)
                ->error("Error saving content", $event->getParams());
            return;
        }

        $content->getType()->getAutoIncrement()->setValue(\intval($connection->lastInsertId()));
        $event->setContent($content);
    }

    public function onDeleteContentEvent(DeleteContentEvent $event): void
    {
        $content = $event->getContent();

        $connection = $this->connectionFactory->getConnection($content->getType()->getDatabase());
        $query = $connection->getRepository()->delete($content->getType(), [
            $content->getType()->getAutoIncrement()->getIdentifier() => $content->getType()->getAutoIncrement()->getValue(),
        ]);

        $result = $query->executeStatement();
        if (\is_numeric($result)) {
            $event->setIsSuccess(TRUE);
        }
    }

    public function onDeleteCollectionEvent(DeleteCollectionEvent $event): void
    {
        $content = $this->contentManager->get($event->getContentIdentifier());

        // @todo reject invalid params

        $connection = $this->connectionFactory->getConnection($content->getType()->getDatabase());
        $query = $connection->getRepository()->delete($content->getType(), $event->getParams());

        $result = $query->executeStatement();
        if (\is_numeric($result)) {
            $event->setDeleteCount(\intval($result));
        }
    }

    public function onUpdateContent(UpdateContentEvent $event): void
    {
        $content = $event->getContent();

        // validate the data
        $inputFilter = new ContentInputFilter($content);

        // set the validation group
        $validationGroup = [];
        foreach (\array_keys($event->getParams()) as $name) {
            if (! $content->getType()->hasProperty($name) || ! $inputFilter->has($name)) {
                continue;
            }

            $validationGroup[$name] = $inputFilter->get($name);
        }

        $inputFilter
            ->setValidationGroup($validationGroup)
            ->setData($event->getParams());

        if (! $inputFilter->isValid()) {
            $event->setErrorMessages($inputFilter->getMessages());
            return;
        }

        // additional validators
        foreach ($content->getConfig()->getValidators() as $name => $options) {
            $options['__operation'] = 'update';
            $validator = $this->validatorPluginManager->get($name, $options);
            if (! $validator->isValid($inputFilter->getValues())) {
                $event->setErrorMessages($validator->getMessages());
                return;
            }
        }

        $connection = $this->connectionFactory->getConnection(
            $content->getType()->getDatabase()
        );

        $update = $connection->getRepository()->update($content->getType(), $inputFilter->getValues());
        if (! \is_numeric($update) || \intval($update) < 1) {
            $event->setErrorMessage('update', "Error updating content");
            $this->getLogger(self::CONTENT_LOGGER)->error("Error updating content", $event->getParams());
            return;
        }

        $content->hydrate($inputFilter->getValues());
        $event->setIsSuccess(TRUE);
    }
}
