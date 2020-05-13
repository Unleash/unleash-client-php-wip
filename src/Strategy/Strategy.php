<?php

namespace Unleash\Strategy;

use Unleash\Context;

class Strategy extends StrategyTransportInterface
{
    public $name;
    private $returnValue;
    public $parameters;

    public function __construct($name = 'unknown', $returnValue = false)
    {
        $this->name = $name;
        $this->returnValue = $returnValue;
    }

    public function isEnabled(array $parameters = null, Context $context = null)
    {
        return $this->returnValue;
    }

    public function normalizeValue($id, $groupId, $normalizer = 100)
    {
        return murmurhash3_int($groupId . ':' . $id) % $normalizer + 1;
    }
}
