<?php

namespace Unleash;

class Feature extends FeatureInterface
{
    public function __construct($name = null, $enabled = null)
    {
        $this->name = $name;
        $this->enabled = $enabled;
    }
}