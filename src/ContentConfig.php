<?php

declare(strict_types=1);

namespace Marshal\ContentManager;

use Marshal\Database\Schema\Type;

class ContentConfig
{
    private Type $type;

    public function __construct(
        private string $database,
        private string $table,
        private array $config,
        private array $properties
    ) {
        $this->type = new Type(
            identifier: "$database::$table",
            database: $database,
            table: $table,
            properties: $properties
        );
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function getValidators(): array
    {
        return $this->config['validators'] ?? [];
    }
}
