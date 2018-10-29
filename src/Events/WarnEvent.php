<?php

namespace Unleash\Events;

use Symfony\Component\EventDispatcher\Event;

class WarnEvent extends Event
{
    private $msg;

    public function __construct($msg)
    {
        $this->msg = $msg;
    }

    public function getMessage()
    {
        return $this->msg;
    }
}