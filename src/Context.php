<?php

namespace Unleash;

class Context
{
    public $userId;
    public $sessionId;
    public $remoteAddress;
    public $properties;

    public function __construct(array $defaults = [])
    {
        foreach ($defaults as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}
