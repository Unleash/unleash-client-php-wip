<?php

namespace Unleash\Strategy;

use Symfony\Component\HttpFoundation\IpUtils;
use Unleash\Context;

class RemoteAddressStrategy extends Strategy
{
    public function __construct()
    {
        parent::__construct('remoteAddress');
    }

    public function isEnabled(array $parameters = [], Context $context = null): bool
    {
        if (empty($parameters['IPs'])) {
            return false;
        }

        $ips = array_map('trim', explode(',', $parameters['IPs']));
        foreach ($ips as $range) {

            try {
                if ($range === $context->remoteAddress) {
                    return true;
                } else if (!filter_var($range, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    if (IpUtils::checkIp($context->remoteAddress, $range)) {
                        return true;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return false;
    }
}