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
use Marshal\Utils\Logger\LoggerManager;
use Marshal\ContentManager\Content;

class WriteContentListener
{
    private const string CONTENT_LOGGER = 'marshal::content';

    public function __construct(private ValidatorPluginManager $validatorPluginManager)
    {
    }

    public function onCreateContent(CreateContentEvent $event): void
    {
        if ($event->hasContent()) {
            return;
        }

        $content = ContentManager::get($event->getContentIdentifier());
        $inputFilter = new ContentInputFilter($content);
        $inputFilter->setData(\array_merge($content->toArray(), $event->getParams()));
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
        $data = $this->normalizeInput($content, $inputFilter->getValues());
        $content->hydrate($data);
        $result = ContentRepository::create($content);
        if (! \is_numeric($result)) {
            $event->setErrorMessage('create', "Error saving content");
            return;
        }

        $content->getAutoIncrement()->setValue(\intval($result));
        $event->setContent($content);
    }

    public function onDeleteContentEvent(DeleteContentEvent $event): void
    {
        $content = $event->getContent();

        $result = ContentRepository::delete($content);
        if (! \is_numeric($result)) {
            $event->setErrorMessage('error', "Error deleting content");
            return;
        }

        $event->setIsSuccess(TRUE);
    }

    public function onDeleteCollectionEvent(DeleteCollectionEvent $event): void
    {
        $content = ContentManager::get($event->getContentIdentifier());

        // @todo reject invalid params

        $result = ContentRepository::delete($content, $event->getParams());
        if (! \is_numeric($result)) {
            $event->setErrorMessage('error', "Error deleting collection");
            return;
        }

        $event->setDeleteCount(\intval($result));
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

        $update = ContentRepository::update($content, $inputFilter->getValues());
        if (! \is_numeric($update) || \intval($update) < 1) {
            $event->setErrorMessage('update', "Error updating content");
            return;
        }

        $content->hydrate($this->normalizeInput($content, $inputFilter->getValues()));
        $event->setIsSuccess(TRUE);
    }

    private function normalizeInput(Content $content, array $input): array
    {
        $data = [];
        foreach ($input as $key => $value) {
            if ($content->hasProperty($key)) {
                $data["{$content->getTable()}__$key"] = $value;
            }
        }

        return $data;
    }
}
