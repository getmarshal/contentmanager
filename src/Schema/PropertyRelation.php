<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Schema;

use Marshal\ContentManager\Content;
use Marshal\ContentManager\ContentManager;

final class PropertyRelation
{
    private const array UPDATE_DELETE_OPTIONS = ['CASCADE', 'SET NULL'];
    private Content $relationContent;

    public function __construct(private readonly array $config)
    {
        $this->relationContent = ContentManager::get($config['schema']);
    }

    public function getAlias(): string
    {
        return $this->config['alias'] ?? $this->getTable();
    }

    public function getOnDelete(): string
    {
        if (! isset($this->config['onDelete'])) {
            return 'CASCADE';
        }

        if (
            ! \is_string($this->config['onDelete'])
            || ! \in_array(\strtoupper($this->config['onDelete']), self::UPDATE_DELETE_OPTIONS, true)
        ) {
            return 'CASCADE';
        }

        return $this->config['onDelete'];
    }

    public function getOnUpdate(): string
    {
        if (! isset($this->config['onUpdate'])) {
            return 'CASCADE';
        }

        if (
            ! \is_string($this->config['onUpdate'])
            || ! \in_array(\strtoupper($this->config['onUpdate']), self::UPDATE_DELETE_OPTIONS, true)
        ) {
            return 'CASCADE';
        }

        return $this->config['onUpdate'];
    }

    public function getProperty(): Property
    {
        return $this->relationContent->getPropertyByIdentifier($this->config['property']);
    }

    public function getRelationContent(): Content
    {
        return $this->relationContent;
    }

    public function getRelationIdentifier(): string
    {
        return $this->relationContent->getTypeIdentifier();
    }

    public function getRelationProperties(): array
    {
        return $this->relationContent->getProperties();
    }

    public function getTable(): string
    {
        return $this->relationContent->getTable();
    }

    public function setRelationContent(Content $content): static
    {
        $this->relationContent = $content;
        return $this;
    }
}
