<?php

declare(strict_types=1);

namespace Marshal\ContentManager\Event;

trait ErrorMessagesTrait
{
    private array $errorMessages = [];

    public function getErrorMessage(string $key): string
    {
        if (! isset($this->errorMessages[$key])) {
            throw new \InvalidArgumentException("Error message $key not found");
        }

        \assert(\is_string($this->errorMessages[$key]));
        
        return $this->errorMessages[$key];
    }

    public function getErrorMessages(): array
    {
        return $this->errorMessages;
    }

    public function setErrorMessage(string $key, string $message): static
    {
        $this->errorMessages[$key] = $message;
        return $this;
    }

    public function setErrorMessages(array $messages): static
    {
        $this->errorMessages = $messages;
        return $this;
    }
}
