<?php

namespace Unleash\Strategy;

use Unleash\Context;

class DefaultStrategy extends Strategy
{
    public function isEnabled(array $parameters = [], Context $context = null): bool
    {
        return true;
    }
}