<?php

namespace Unleash\Strategy;

use Unleash\Context;

class GradualRolloutSessionIdStrategy extends Strategy
{
    public function __construct()
    {
        parent::__construct('gradualRolloutSessionId');
    }

    public function isEnabled(array $parameters = [], Context $context = null): bool
    {
        if (!$context->sessionId){
            return false;
        }

        $percentage = (int) $parameters['percentage'];
        $groupId = $parameters['groupId'] ?? '';


        $normalizedId = normalizeValue($context->sessionId, $groupId);

        return $percentage > 0 && $normalizedId <= $percentage;
    }
}