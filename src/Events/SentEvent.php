<?php
declare(strict_types=1);

namespace Unleash\Events;

use Symfony\Component\EventDispatcher\Event;

class SentEvent extends Event
{
    private $payload;

    public function __construct($payload)
    {
        $this->payload = $payload;
    }
}
