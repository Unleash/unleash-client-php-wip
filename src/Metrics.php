<?php

namespace Unleash;

use Symfony\Component\EventDispatcher\EventDispatcher;

class Metrics extends EventDispatcher
{
    public function count(string $toggleName, bool $enabled)
    {
        //@todo: implement
    }

    public function stop()
    {
        //@todo: implement
    }
}