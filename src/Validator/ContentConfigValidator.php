<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Validator;

use Laminas\Validator\AbstractValidator;

class ContentConfigValidator extends AbstractValidator
{
    public const string INVALID_CONFIG = 'invalidConfig';
    public array $messageTemplates = [
        self::INVALID_CONFIG => 'Invalid config. Must be an an instance of ContentConfig',
    ];

    public function __construct(private array $config)
    {
    }

    public function isValid(mixed $value): bool
    {
        if (! isset($this->config[$value])) {
            $this->error(self::INVALID_CONFIG);
            return FALSE;
        }

        if (! $this->isValidPropertyIdentifier($value)) {
            return FALSE;
        }

        if (! isset($this->config[$value]['properties'])) {
            $this->error(self::INVALID_CONFIG);
            return FALSE;
        }

        foreach ($this->config[$value]['properties'] as $propertyName => $propertyConfig) {
            if (! \is_string($propertyName)) {
                $this->error(self::INVALID_CONFIG);
                return FALSE;
            }

            if (! $this->isValidProperty($propertyConfig)) {
                return FALSE;
            }
        }

        return TRUE;
    }

    private function isValidProperty(array $propertyConfig): bool
    {
        if (isset($propertyConfig['index'])) {
            if (! \is_array($propertyConfig['index']) && ! \is_bool($propertyConfig['index'])) {
                $this->error(self::INVALID_CONFIG);
                return FALSE;
            }

            if (! $this->isValidPropertyIndex($propertyConfig['index'])) {
                return FALSE;
            }
        }

        if (isset($propertyConfig['relation'])) {
            if (! \is_array($propertyConfig['relation'])) {
                $this->error(self::INVALID_CONFIG);
                return FALSE;
            }

            if (! $this->isValidPropertyRelation($propertyConfig['relation'])) {
                return FALSE;
            }
        }

        return TRUE;
    }

    private function isValidPropertyIdentifier(string $identifier): bool
    {
        if (! \is_string($identifier)) {
            $this->error(self::INVALID_CONFIG);
            return FALSE;
        }

        $nameParts = \explode('::', $identifier);
        if (\count($nameParts) !== 2) {
            $this->error(self::INVALID_CONFIG);
            return FALSE;
        }

        return TRUE;
    }

    private function isValidPropertyIndex(array|bool $indexConfig): bool
    {
        if (\is_bool($indexConfig)) {
            return TRUE;
        }

        return TRUE;
    }

    private function isValidPropertyRelation(array $relationConfig): bool
    {
        if (! isset($relationConfig['schema'])) {
            $this->error(self::INVALID_CONFIG);
            return FALSE;
        }

        if (! isset($relationConfig['property'])) {
            $this->error(self::INVALID_CONFIG);
            return FALSE;
        }

        $split = \explode('::', $relationConfig['schema']);
        if (\count($split) !== 2) {
            $this->error(self::INVALID_CONFIG);
            return FALSE;
        }

        return TRUE;
    }
}
