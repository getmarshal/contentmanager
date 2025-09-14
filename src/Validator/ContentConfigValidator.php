<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Validator;

use Laminas\Validator\AbstractValidator;

class ContentConfigValidator extends AbstractValidator
{
    private const string IDENTIFIER_NOT_FOUND = 'identifierNotFound';
    private const string INVALID_CONTENT_IDENTIFIER = 'invalidContentIdentifier';
    private const string INVALID_INDEX_CONFIG = 'invalidIndexConfig';
    private const string INVALID_PROPERTIES_CONFIGURED = 'invalidPropertiesConfigured';
    private const string INVALID_PROPERTY_NAME = 'invalidPropertyName';
    private const string INVALID_RELATION_CONFIG = 'invalidRelationConfig';
    private const string PROPERY_RELATION_SCHEMA_NOT_SPECIFIED = 'noPropertyRelationSchema';
    private const string PROPERY_RELATION_PROPERTY_NOT_SPECIFIED = 'noPropertyRelationProperty';
    public array $messageTemplates = [
        self::IDENTIFIER_NOT_FOUND => "Content identifier %value% not found in config",
        self::INVALID_CONTENT_IDENTIFIER => 'Invalid content identifier %value%. Must contain format `database::table`',
        self::INVALID_INDEX_CONFIG => 'Invalid index config %value%',
        self::INVALID_PROPERTIES_CONFIGURED => 'Content schema %value% properties empty or not configured',
        self::INVALID_PROPERTY_NAME => 'Invalid property name %value%',
        self::INVALID_RELATION_CONFIG => 'Invalid relation config %value%',
        self::PROPERY_RELATION_SCHEMA_NOT_SPECIFIED => "Property relation %value% schema key not specified",
        self::PROPERY_RELATION_PROPERTY_NOT_SPECIFIED => "Property relation %value% property key not specified",
    ];

    public function __construct(private array $config)
    {
        parent::__construct();
    }

    public function isValid(mixed $value): bool
    {
        if (! isset($this->config[$value])) {
            $this->setValue($value);
            $this->error(self::IDENTIFIER_NOT_FOUND);
            return FALSE;
        }

        if (! $this->isValidPropertyIdentifier($value)) {
            return FALSE;
        }

        if (! isset($this->config[$value]['properties']) || ! \is_array($this->config[$value]['properties'])) {
            $this->setValue($value);
            $this->error(self::INVALID_PROPERTIES_CONFIGURED);
            return FALSE;
        }

        foreach ($this->config[$value]['properties'] as $propertyName => $propertyConfig) {
            if (! \is_string($propertyName)) {
                $this->setValue(sprintf("%s on schema %s", \get_debug_type($propertyName), $value));
                $this->error(self::INVALID_PROPERTY_NAME);
                return FALSE;
            }

            if (! $this->isValidProperty($value, $propertyName, $propertyConfig)) {
                return FALSE;
            }
        }

        return TRUE;
    }

    private function isValidProperty(string $schemaName, string $propertyName, array $propertyConfig): bool
    {
        if (isset($propertyConfig['index'])) {
            if (! \is_array($propertyConfig['index']) && ! \is_bool($propertyConfig['index'])) {
                $this->setValue("on property $propertyName, schema $schemaName");
                $this->error(self::INVALID_INDEX_CONFIG);
                return FALSE;
            }

            if (! $this->isValidPropertyIndex($propertyConfig['index'])) {
                return FALSE;
            }
        }

        if (isset($propertyConfig['relation'])) {
            if (! \is_array($propertyConfig['relation'])) {
                $this->setValue("on property $propertyName, schema $schemaName");
                $this->error(self::INVALID_RELATION_CONFIG);
                return FALSE;
            }

            if (! $this->isValidPropertyRelation($schemaName, $propertyName, $propertyConfig['relation'])) {
                return FALSE;
            }
        }

        return TRUE;
    }

    private function isValidPropertyIdentifier(string $identifier): bool
    {
        $nameParts = \explode('::', $identifier);
        if (\count($nameParts) !== 2) {
            $this->setValue($identifier);
            $this->error(self::INVALID_CONTENT_IDENTIFIER);
            return FALSE;
        }

        return TRUE;
    }

    private function isValidPropertyIndex(array|bool $indexConfig): bool
    {
        return TRUE;
    }

    private function isValidPropertyRelation(string $schemaName, string $propertyName, array $relationConfig): bool
    {
        if (! isset($relationConfig['schema']) || ! \is_string($relationConfig['schema'])) {
            $this->setValue(\sprintf("%s on schema %s", $propertyName, $schemaName));
            $this->error(self::PROPERY_RELATION_SCHEMA_NOT_SPECIFIED);
            return FALSE;
        }

        $split = \explode('::', $relationConfig['schema']);
        if (\count($split) !== 2) {
            $this->setValue(\sprintf("%s on schema %s", $relationConfig['schema'], $schemaName));
            $this->error(self::INVALID_CONTENT_IDENTIFIER);
            return FALSE;
        }

        if (! isset($relationConfig['property'])) {
            $this->setValue(\sprintf("%s on schema %s", $propertyName, $schemaName));
            $this->error(self::PROPERY_RELATION_PROPERTY_NOT_SPECIFIED);
            return FALSE;
        }

        return TRUE;
    }
}
