<?php

declare(strict_types=1);

namespace Marshal\ContentManager;

use Marshal\Util\Database\Schema\SchemaManager;

final class ContentManager
{
    public function __construct(private SchemaManager $schemaManager)
    {
    }

    public function get($name): Content
    {
        return new Content($this->schemaManager->get($name));
    }

    /**
     * @return Content[]
     */
    public function getAll(): array
    {
        $content = [];
        foreach ($this->schemaManager->getAll() as $type) {
            $content[$type->getIdentifier()] = new Content($type);
        }

        return $content;
    }
}
