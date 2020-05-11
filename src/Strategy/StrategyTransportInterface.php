<?php

namespace Unleash\Strategy;

class StrategyTransportInterface
{
    public $name;
    public $parameters;

    public function __construct($name, array $parameters = null)
    {
        $this->name = $name;
        $this->parameters = $parameters;
    }
}
