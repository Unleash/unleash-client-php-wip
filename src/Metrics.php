<?php

namespace Unleash;

use Symfony\Component\EventDispatcher\EventDispatcher;

class Metrics extends EventDispatcher
{
    private $bucket;
    private $appName;
    private $instanceId;
    private $sdkVersion;
    private $strategies;
    private $metricsInterval;
    private $disabled;
    private $bucketInterval;
    private $url;
    private $timer;
    private $started;
    private $headers;

    public function __construct(
        string $appName,
        string $instanceId,
        array $strategies,
        string $url,
        array $headers,
        int $metricsInterval = 0,
        bool $disableMetrics = false
    ) {
        $this->disabled = $disableMetrics;
        $this->metricsInterval = $metricsInterval;
        $this->appName = $appName;
        $this->instanceId = $instanceId;
        $this->sdkVersion = '';//@todo: get the correct version
        $this->strategies = $strategies;
        $this->url = $url;
        $this->headers = $headers;
        $this->started = new \DateTime();
        $this->resetBucket();

        if ($this->metricsInterval > 0) {
            $this->startTimer();
            $this->registerInstance();
        }
    }

    public function startTimer()
    {
        if ($this->disabled) {
            return false;
        }

        return true;
    }

    public function count(string $toggleName, bool $enabled)
    {
        //@todo: implement
    }

    public function stop()
    {
        //@todo: implement
    }
}