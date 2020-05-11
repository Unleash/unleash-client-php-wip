<?php

namespace Unleash\Strategy;

use Unleash\Context;

class Strategy extends StrategyTransportInterface
{
    public $name;
    private $returnValue;
    public $parameters;

    public function __construct(string $name = 'unknown', bool $returnValue = false)
    {
        $this->name = $name;
        $this->returnValue = $returnValue;
    }

    public function isEnabled(array $parameters = null, Context $context = null)
    {
        return $this->returnValue;
    }
}
