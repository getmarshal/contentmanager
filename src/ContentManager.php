<?php

/**
 * Content Manager
 */

declare(strict_types=1);

namespace Marshal\ContentManager;

use Laminas\ServiceManager\AbstractPluginManager;
use Marshal\Database\Schema\Property;
use Marshal\Database\Schema\PropertyConfig;
use Marshal\Database\Schema\PropertyRelation;
use Psr\Container\ContainerInterface;

final class ContentManager extends AbstractPluginManager
{
    protected $instanceOf = Content::class;
    private array $validationMessages = [];

    public function __construct(private ContainerInterface $parentContainer, private array $config)
    {
        parent::__construct($parentContainer);
    }

    public function get($name, ?array $options = null): Content
    {
        $validator = new Validator\ContentConfigValidator($this->config);
        if (! $validator->isValid($name)) {
            throw new Exception\InvalidContentConfigException($name, $validator->getMessages());
        }

        // build properties
        $properties = [];
        foreach ($this->config[$name]['properties'] as $identifier => $definition) {
            $properties[$identifier] = $this->buildProperty($identifier, $definition);
        }

        // create the content class
        // config already validated
        $nameSplit = \explode('::', $name);
        $config = $this->config[$name];
        return new Content(new ContentConfig(
            $nameSplit[0],
            $nameSplit[1],
            $config,
            $properties
        ));
    }

    /**
     * @return Content[]
     */
    public function getAllTypes(): array
    {
        $types = [];
        foreach (\array_keys($this->config) as $name) {
            $types[$name] = $this->get($name);
        }

        return $types;
    }

    private function buildProperty(string $identifier, array $definition): Property
    {
        if (isset($definition['relation']) && \is_array($definition['relation'])) {
            $relation = $this->get($definition['relation']['schema']);
            $definition['relation'] = new PropertyRelation(
                $relation->getType(),
                $relation->getConfig(),
                $definition['relation']['property'],
                $definition['relation'],
                $identifier
            );
        }

        $propertyConfig = new PropertyConfig($definition);
        return new Property($identifier, $propertyConfig);
    }
}
