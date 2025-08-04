<?php

/**
 * Content Manager
 */

declare(strict_types=1);

namespace Marshal\ContentManager;

use Laminas\ServiceManager\AbstractPluginManager;
use Marshal\ContentManager\Schema\Content;
use Marshal\ContentManager\Schema\Property;
use Marshal\ContentManager\Schema\PropertyConfig;
use Marshal\ContentManager\Schema\PropertyRelation;
use Psr\Container\ContainerInterface;

final class ContentManager extends AbstractPluginManager
{
    protected $instanceOf = Content::class;

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
        return new Content(
            $nameSplit[0],
            $nameSplit[1],
            $config,
            $properties
        );
    }

    private function buildProperty(string $identifier, array $definition): Property
    {
        if (isset($definition['relation'])) {
            $definition['relation'] = new PropertyRelation(
                $identifier,
                $definition['relation'],
                $this->get($definition['relation']['schema'])
            );
        }

        return new Property($identifier, new PropertyConfig($definition));
    }
}
