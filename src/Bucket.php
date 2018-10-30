<?php

namespace Unleash;

class Bucket
{
    /** @var \DateTime */
    public $start;
    /** @var \DateTime|null */
    public $stop = null;
    /** @var array */
    public $toggles = [];
}