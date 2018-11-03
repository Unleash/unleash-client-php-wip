<?php

namespace Unleash\Strategy;

use Unleash\Context;

class GradualRolloutUserIdStrategy extends Strategy
{
    public function __construct()
    {
        parent::__construct('gradualRolloutUserId');
    }

    public function isEnabled(array $parameters = null, Context $context = null): bool
    {
        if (empty($context->userId) || !isset($parameters['percentage'])) {
            return false;
        }

        $percentage = (int)$parameters['percentage'];
        $groupId = $parameters['groupId'] ?? '';
        $normalizedUserId = normalizeValue($context->userId, $groupId);
        return $percentage > 0 && $normalizedUserId <= $percentage;
    }
}
