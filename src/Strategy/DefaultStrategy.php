<?php

namespace Unleash\Strategy;

use Unleash\Context;

class DefaultStrategy extends Strategy
{
    public function isEnabled(array $parameters = null, Context $context = null): bool
    {
        return true;
    }
}
