<?php

namespace Unleash\Strategy;

use Unleash\Context;

class DefaultStrategy extends Strategy
{
    public function __construct()
    {
        parent::__construct('default');
    }

    public function isEnabled(array $parameters = null, Context $context = null)
    {
        return true;
    }
}
