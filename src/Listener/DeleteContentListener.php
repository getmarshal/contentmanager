<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Listener;

use Marshal\EventManager\EventListenerInterface;

class DeleteContentListener implements EventListenerInterface
{
    public function getListeners(): array
    {
        return [];
    }
}
