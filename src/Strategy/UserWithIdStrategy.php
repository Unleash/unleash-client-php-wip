<?php

namespace Unleash\Strategy;

use Unleash\Context;

class UserWithIdStrategy extends Strategy
{
    public function __construct()
    {
        parent::__construct('userWithId');
    }

    public function isEnabled(array $parameters = null, Context $context = null)
    {
        if (!isset($parameters['userIds'])) {
            return false;
        }

        $userIds = array_map('trim', explode(',', $parameters['userIds']));
        return in_array($context->userId, $userIds);
    }
}
