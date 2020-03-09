<?php
declare(strict_types=1);

namespace Unleash\Strategy;

use Unleash\Context;

class ApplicationHostnameStrategy extends Strategy
{
    /** @var string */
    private $hostname;

    public function __construct()
    {
        parent::__construct('applicationHostname');
        $this->hostname = strtolower($_SERVER['host_name'] ?? 'undefined');
    }

    public function isEnabled(array $parameters = null, Context $context = null): bool
    {
        if (!isset($parameters['hostNames'])) {
            return false;
        }

        return in_array($this->hostname, array_map('trim', explode(',', strtolower($parameters['hostNames']))), true);
    }
}
