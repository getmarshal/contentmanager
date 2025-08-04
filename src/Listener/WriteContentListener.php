<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Listener;

use Laminas\Validator\ValidatorPluginManager;
use Marshal\ContentManager\ContentManager;
use Marshal\ContentManager\ContentRepository;
use Marshal\ContentManager\Event\CreateContentEvent;
use Marshal\ContentManager\Event\DeleteCollectionEvent;
use Marshal\ContentManager\Event\DeleteContentEvent;
use Marshal\ContentManager\Event\UpdateContentEvent;
use Marshal\ContentManager\InputFilter\ContentInputFilter;
use Marshal\EventManager\EventListenerInterface;
use Marshal\Util\Logger\LoggerFactoryAwareInterface;
use Marshal\Util\Logger\LoggerFactoryTrait;

class WriteContentListener implements EventListenerInterface, LoggerFactoryAwareInterface
{
    use LoggerFactoryTrait;

    private const string CONTENT_LOGGER = 'marshal::content';

    public function __construct(
        private ContentRepository $contentRepository,
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
        if ($event->hasContent()) {
            return;
        }

        $content = $this->contentManager->get($event->getContentIdentifier());

        $inputFilter = new ContentInputFilter($content);
        $inputFilter->setData($event->getParams());
        if (! $inputFilter->isValid()) {
            $event->setErrorMessages($inputFilter->getMessages());
            return;
        }

        // additional validators
        foreach ($content->getValidators() as $name => $options) {
            $options['__operation'] = 'create';
            $validator = $this->validatorPluginManager->get($name, $options);
            if (! $validator->isValid($inputFilter->getValues())) {
                $event->setErrorMessages($validator->getMessages());
                return;
            }
        }

        // hydrate the content with the filtered, validated input
        $content->hydrate($inputFilter->getValues());

        $result = $this->contentRepository->create($content);
        if (! \is_numeric($result)) {
            $event->setErrorMessage('create', "Error saving content");
            $this->getLogger(self::CONTENT_LOGGER)
                ->error("Error saving content", $event->getParams());
            return;
        }

        $content->getAutoIncrement()->setValue(\intval($result));
        $event->setContent($content);
    }

    public function onDeleteContentEvent(DeleteContentEvent $event): void
    {
        $content = $event->getContent();

        $query = $this->contentRepository->delete($content, [
            $content->getAutoIncrement()->getIdentifier() => $content->getAutoIncrement()->getValue(),
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

        $query = $this->contentRepository->delete($content, $event->getParams());

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
            if (! $content->hasProperty($name) || ! $inputFilter->has($name)) {
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
        foreach ($content->getValidators() as $name => $options) {
            $options['__operation'] = 'update';
            $validator = $this->validatorPluginManager->get($name, $options);
            if (! $validator->isValid($inputFilter->getValues())) {
                $event->setErrorMessages($validator->getMessages());
                return;
            }
        }

        $update = $this->contentRepository->update($content, $inputFilter->getValues());
        if (! \is_numeric($update) || \intval($update) < 1) {
            $event->setErrorMessage('update', "Error updating content");
            $this->getLogger(self::CONTENT_LOGGER)->error("Error updating content", $event->getParams());
            return;
        }

        $content->hydrate($inputFilter->getValues());
        $event->setIsSuccess(TRUE);
    }
}
