<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Exception;

class InvalidContentConfigException extends \InvalidArgumentException
{
    public function __construct(string $name, array $messages)
    {
        parent::__construct("Invalid content config $name");
    }
}
