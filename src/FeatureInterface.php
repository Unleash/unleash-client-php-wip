<?php
declare(strict_types=1);

namespace Unleash;

use Unleash\Strategy\StrategyTransportInterface;

abstract class FeatureInterface
{
    /** @var string */
    public $name;
    /** @var string */
    public $description;
    /** @var bool */
    public $enabled;
    /** @var StrategyTransportInterface[] */
    public $strategies;
}
