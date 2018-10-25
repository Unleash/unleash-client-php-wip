<?php

namespace Unleash\Strategy;

use Unleash\Context;

abstract class Strategy
{
    public $name;
    private $returnValue;

    public function __construct(string $name = 'unknown', bool $returnValue = false)
    {
        $this->name = $name;
        $this->returnValue = $returnValue;
    }

    public function isEnabled(array $parameters = [], Context $context = null): bool {
        return $this->returnValue;
    }
}