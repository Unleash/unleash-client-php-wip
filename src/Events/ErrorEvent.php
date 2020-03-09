<?php
declare(strict_types=1);

namespace Unleash\Events;

use Symfony\Component\EventDispatcher\Event;

class ErrorEvent extends Event
{
    private $err;

    public function __construct(array $err = [])
    {
        $this->err = $err;
    }

    public function getError()
    {
        return $this->err;
    }

    public function setError(string $error)
    {
        $this->err = $error;
    }
}
