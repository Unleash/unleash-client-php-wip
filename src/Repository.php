<?php

namespace Unleash;

use Symfony\Component\EventDispatcher\EventDispatcher;

class Repository extends EventDispatcher
{
    public function __construct(
        string $backupPath,
        string $url,
        string $appName,
        string $interfaceId,
        string $refreshInterval = null,
        array $customHeaders = [],
        $storageImpl = null
    ) {

    }

    public function getToggle(string $name): FeatureInterface
    {
        //@todo: implement
    }

    public function stop()
    {
        //@todo: implement
    }
}