<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Schema;

use Marshal\Application\Config;
use Marshal\ContentManager\Exception\InvalidTypeConfigException;
use Marshal\ContentManager\Exception\InvalidPropertyConfigException;
use Marshal\ContentManager\Validator\PropertyConfigValidator;
use Marshal\ContentManager\Validator\TypeConfigValidator;

final class TypeManager
{
    private function __construct()
    {
    }

    private function __clone(): void
    {
    }

    public static function get($identifier): Type
    {
        $schema = Config::get('schema');
        $typesConfig = $schema['types'] ?? [];

        // validate the type
        $typeValidator = new TypeConfigValidator($typesConfig);
        if (! $typeValidator->isValid($identifier)) {
            throw new InvalidTypeConfigException($identifier, $typeValidator->getMessages());
        }

        $config = $typesConfig[$identifier];
        $type = new Type(
            identifier: $identifier,
            database: $config['database'] ?? \explode('::', $identifier)[0],
            table: $config['table'] ?? \explode('::', $identifier)[1],
            config: $config
        );

        foreach ($config['inherits'] ?? [] as $inheritFrom) {
            $parent = self::get($inheritFrom);
            foreach ($parent->getProperties() as $parentProperty) {
                if (\in_array($parentProperty->getIdentifier(), $config['exclude_properties'] ?? [])) {
                    continue;
                }
                $type->setProperty($parentProperty);
            }
        }

        // add type properties
        $propsConfig = $schema['properties'] ?? [];
        $propertyValidator = new PropertyConfigValidator($propsConfig);
        foreach ($config['properties'] ?? [] as $propertyIdentifier => $definition) {
            if (! $propertyValidator->isValid($propertyIdentifier)) {
                throw new InvalidPropertyConfigException($propertyIdentifier, $propertyValidator->getMessages());
            }

            if ($type->hasPropertyByIdentifier($propertyIdentifier)) {
                $property = $type->getPropertyByIdentifier($propertyIdentifier);
                $property->prepareFromDefinition($definition);
                $type->removePropertyByIdentifier($property->getIdentifier());
                $type->setProperty($property);
            } else {
                $fullDefinition = \array_merge($propsConfig[$propertyIdentifier], $definition);
                $property = isset($fullDefinition['relation'])
                    ? new Property($propertyIdentifier, $fullDefinition, new PropertyRelation($fullDefinition['relation']))
                    : new Property($propertyIdentifier, $fullDefinition);
                $type->setProperty($property);
            }
        }

        // remove excluded properties
        foreach ($config['exclude_properties'] ?? [] as $identifier) {
            $type->removePropertyByIdentifier($identifier);
        }

        return $type;
    }
}
