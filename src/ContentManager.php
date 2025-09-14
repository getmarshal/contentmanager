<?php

declare(strict_types=1);

namespace Marshal\ContentManager;

use Marshal\Util\Database\Exception\InvalidContentConfigException;
use Marshal\Util\Database\Schema\Property;
use Marshal\Util\Database\Schema\PropertyRelation;
use Marshal\Util\Database\Schema\Type;
use Marshal\Util\Database\Validator\PropertyConfigValidator;
use Marshal\Util\Database\Validator\TypeConfigValidator;

final class ContentManager
{
    public function __construct(
        private TypeConfigValidator $typeValidator,
        private PropertyConfigValidator $propertyValidator,
        private array $typesConfig,
        private array $propertiesConfig
    ) {
    }

    public function get($name): Content
    {
        return new Content($this->getType($name));
    }

    private function getType(string $name): Type
    {
        if (! $this->typeValidator->isValid($name)) {
            throw new InvalidContentConfigException($name, $this->typeValidator->getMessages());
        }

        $nameSplit = \explode('::', $name);

        $type = new Type(
            identifier: $name,
            database: $nameSplit[0],
            table: $nameSplit[1],
            config: $this->typesConfig[$name]
        );

        foreach ($this->typesConfig[$name]['inherits'] ?? [] as $identifier) {
            $type->addParent($this->getType($identifier));
        }

        foreach ($this->typesConfig[$name]['properties'] ?? [] as $identifier => $definition) {
            if (! $this->propertyValidator->isValid($identifier)) {
                throw new InvalidContentConfigException($name, $this->propertyValidator->getMessages());
            }

            if ($type->hasPropertyIdentifier($identifier)) {
                $property = $type->getPropertyByIdentifier($identifier);
                $property->prepareFromDefinition($definition);
                $type->setProperty($property);
            } else {
                $fullDefinition = \array_merge($this->propertiesConfig[$identifier], $definition);
                if (isset($fullDefinition['relation'])) {
                    $fullDefinition['relation'] = new PropertyRelation(
                        $this->getType($fullDefinition['relation']['schema']),
                        $fullDefinition['relation']
                    );
                }

                $type->setProperty(new Property($identifier, $fullDefinition));
            }
        }

        // remove excluded properties
        foreach ($this->typesConfig[$name]['exclude_properties'] ?? [] as $identifier) {
            $type->removeProperty($identifier);
        }

        return $type;
    }
}
