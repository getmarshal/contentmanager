<?php

declare(strict_types=1);

namespace Marshal\ContentManager;

use Marshal\Application\Config;
use Marshal\ContentManager\Exception\InvalidTypeConfigException;
use Marshal\ContentManager\Exception\InvalidPropertyConfigException;
use Marshal\ContentManager\Schema\Property;
use Marshal\ContentManager\Schema\PropertyRelation;
use Marshal\ContentManager\Schema\Type;
use Marshal\ContentManager\Validator\PropertyConfigValidator;
use Marshal\ContentManager\Validator\TypeConfigValidator;

final class ContentManager
{
    private function __construct()
    {
    }

    private function __clone(): void
    {
    }

    public static function get($name): Content
    {
        $schema = Config::get('schema');
        $typesConfig = $schema['types'] ?? [];
        
        // validate the type
        $typeValidator = new TypeConfigValidator($typesConfig);
        if (! $typeValidator->isValid($name)) {
            throw new InvalidTypeConfigException($name, $typeValidator->getMessages());
        }

        $nameSplit = \explode('::', $name);

        $type = new Type(
            identifier: $name,
            database: $nameSplit[0],
            table: $nameSplit[1],
            config: $typesConfig[$name]
        );

        foreach ($typesConfig[$name]['inherits'] ?? [] as $identifier) {
            $type->addParent(self::get($identifier)->getType());
        }

        // add type properties
        $propsConfig = $schema['properties'] ?? [];
        $propertyValidator = new PropertyConfigValidator($propsConfig);
        foreach ($typesConfig[$name]['properties'] ?? [] as $identifier => $definition) {
            if (! $propertyValidator->isValid($identifier)) {
                throw new InvalidPropertyConfigException($name, $propertyValidator->getMessages());
            }

            if ($type->hasPropertyIdentifier($identifier)) {
                $property = $type->getPropertyByIdentifier($identifier);
                $property->prepareFromDefinition($definition);
                $type->setProperty($property);
            } else {
                $fullDefinition = \array_merge($propsConfig[$identifier], $definition);
                if (isset($fullDefinition['relation'])) {
                    $fullDefinition['relation'] = new PropertyRelation(
                        self::get($fullDefinition['relation']['schema'])->getType(),
                        $fullDefinition['relation']
                    );
                }

                $type->setProperty(new Property($identifier, $fullDefinition));
            }
        }

        // remove excluded properties
        foreach ($typesConfig[$name]['exclude_properties'] ?? [] as $identifier) {
            $type->removeProperty($identifier);
        }

        return new Content($type);
    }
}
