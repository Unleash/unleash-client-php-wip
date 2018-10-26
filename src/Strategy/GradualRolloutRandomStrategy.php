<?php

namespace Unleash\Strategy;

use Unleash\Context;

class GradualRolloutRandomStrategy extends Strategy
{
    public function __construct()
    {
        parent::__construct('gradualRolloutRandom');
    }

    public function isEnabled(array $parameters = [], Context $context = null): bool
    {
        $percentage = $parameters['percentage'];
        $random = round((mt_rand() / mt_getrandmax()) * 100);
        if ($random === 0) {
            return false;
        }

        return $percentage >= $random;
    }
}