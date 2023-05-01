<?php

namespace App\Event;

use App\Contract\EventInterface;
use App\Trait\StoppableEventTrait;
use Ramsey\Uuid\UuidInterface;

class UserCreateEvent implements EventInterface
{
    use StoppableEventTrait;

    public function __construct(
        private string $username,
        private UuidInterface $uuid
    ) {
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }
}
