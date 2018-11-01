<?php

namespace Unleash\Strategy;

use Unleash\Context;

class GradualRolloutRandomStrategy extends Strategy
{
    public function __construct()
    {
        parent::__construct('gradualRolloutRandom');
    }

    public function isEnabled(array $parameters = null, Context $context = null): bool
    {
        if (!isset($parameters['percentage'])) {
            return false;
        }

        $percentage = $parameters['percentage'];
        $random = round((mt_rand() / mt_getrandmax()) * 100);

        return $percentage >= $random;
    }
}
