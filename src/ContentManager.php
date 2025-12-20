<?php

declare(strict_types=1);

namespace Marshal\ContentManager;

use Marshal\ContentManager\Schema\TypeManager;

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
        return new Content(TypeManager::get($name));
    }
}
