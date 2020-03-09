<?php
declare(strict_types=1);

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
