<?php

/**
 *
 */

declare(strict_types=1);

namespace Marshal\ContentManager\Exception;

final class CreateContentException extends \RuntimeException
{
    public function __construct(array $messages)
    {
        parent::__construct("");
    }
}
