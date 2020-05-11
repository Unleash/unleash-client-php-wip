<?php

namespace Unleash\Strategy;

use Unleash\Context;

class GradualRolloutRandomStrategy extends Strategy
{
    public function __construct()
    {
        parent::__construct('gradualRolloutRandom');
    }

    public function isEnabled(array $parameters = null, Context $context = null)
    {
        if (!isset($parameters['percentage'])) {
            return false;
        }

        $percentage = $parameters['percentage'];
        $random = mt_rand(0,100);
        if ($random === 0) {
            return false;
        }

        return $percentage >= $random;
    }
}
