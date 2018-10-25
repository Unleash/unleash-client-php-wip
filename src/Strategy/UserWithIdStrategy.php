<?php

namespace Unleash\Strategy;

use Unleash\Context;

class UserWithIdStrategy extends Strategy
{
    public function __construct()
    {
        parent::__construct('userWithId');
    }

    public function isEnabled(array $parameters = [], Context $context = null): bool
    {
        $userIds = array_map('trim', explode(',', $parameters['userIds']));
        return in_array($context->userId, $userIds);
    }
}