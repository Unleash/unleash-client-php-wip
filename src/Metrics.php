<?php

namespace Unleash;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Unleash\Events\CountEvent;
use Unleash\Events\ErrorEvent;
use Unleash\Events\RegisterEvent;
use Unleash\Events\SentEvent;
use Unleash\Events\WarnEvent;

class Metrics extends EventDispatcher
{
    /** @var Bucket */
    private $bucket;
    private $appName;
    private $instanceId;
    private $sdkVersion;
    private $strategies;
    private $metricsInterval;
    private $disabled;
    private $url;
    private $timer;
    private $started;
    private $headers;
    private $client;


    public function __construct(
        $appName,
        $instanceId,
        array $strategies,
        $url,
        array $headers,
        $metricsInterval = 0,
        $disableMetrics = false,
        Client $client = null
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
        if ($client === null) {
            $client = new Client([
                'base_uri' => $url,
            ]);
        }
        $this->client = $client;

        $this->resetBucket();
    }

    public function init()
    {
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

        $this->timer = new \EvTimer($this->metricsInterval, 0, function () {
            $this->sendMetrics();
        });

        if (getenv('env') !== 'test') {
            \Ev::run(\Ev::RUN_NOWAIT);
        } else {
            \Ev::run();
        }

        return true;
    }

    public function stop()
    {
        \Ev::stop();
        $this->timer = null;
        $this->disabled = true;
    }

    public function registerInstance()
    {
        if ($this->disabled) {
            return false;
        }

        $payload = $this->getClientData();
        $options = $this->createCurlOptions($payload);
        $url = '/client/register';
        try {
            $response = $this->client->request('post', $url, $options);
        } catch (ClientException $exception) {
            $response = $exception->getResponse();
        } catch (ServerException $exception) {
            $this->dispatch('error', new ErrorEvent(['message' => $exception->getMessage()]));
            return false;
        }

        if (!($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            $this->dispatch('warn', new WarnEvent($url . ' returning ' . $response->getStatusCode()));
            return false;
        }

        $this->dispatch('registered', new RegisterEvent($payload));

        return true;
    }

    public function sendMetrics()
    {
        if ($this->disabled) {
            return false;
        }
        if ($this->bucketIsEmpty()) {
            $this->resetBucket();
            $this->startTimer();
            return true;
        }

        $payload = $this->getPayload();
        $options = $this->createCurlOptions($payload);
        $url = '/client/metrics';
        try {
            $response = $this->client->request('post', $url, $options);
        } catch (ClientException $exception) {
            if ($exception->getResponse()->getStatusCode() === 404) {
                $this->dispatch('warn', new WarnEvent($url . ' returning 404, stopping metrics'));
                $this->stop();
                return false;
            }

            $response = $exception->getResponse();
        } catch (ServerException $exception) {
            $this->dispatch('error', new ErrorEvent(['message' => $exception->getMessage()]));
            return false;
        }

        if (!($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            $this->dispatch('warn', new WarnEvent($url . ' returning ' . $response->getStatusCode()));
            return false;
        }

        $this->dispatch('sent', new SentEvent($payload));
        return true;
    }

    public function count($name = null, $enabled = false)
    {
        if ($this->disabled) {
            return false;
        }

        if (!isset($this->bucket->toggles[$name])) {
            $this->bucket->toggles[$name] = [
                'yes' => 0,
                'no'  => 0,
            ];
        }

        $this->bucket->toggles[$name][$enabled ? 'yes' : 'no']++;
        $this->dispatch('count', new CountEvent($name, $enabled));

        return true;
    }

    public function bucketIsEmpty()
    {
        return count(array_keys($this->bucket->toggles)) === 0;
    }

    public function resetBucket()
    {
        $bucket = new Bucket();
        $bucket->start = new \DateTime();
        $bucket->stop = null;
        $bucket->toggles = [];
        $this->bucket = $bucket;
    }

    public function closeBucket()
    {
        $this->bucket->stop = new \DateTime();
    }

    public function getPayload()
    {
        $this->closeBucket();
        $payload = $this->getMetricsData();
        $this->resetBucket();
        return $payload;
    }

    public function getClientData()
    {
        return [
            'appName'    => $this->appName,
            'instanceId' => $this->instanceId,
            'sdkVersion' => $this->sdkVersion,
            'strategies' => $this->strategies,
            'interval'   => $this->metricsInterval,
        ];
    }

    public function getMetricsData()
    {
        return [
            'appName'    => $this->appName,
            'instanceId' => $this->instanceId,
            'bucket'     => (array)$this->bucket,
        ];
    }

    /**
     * @param array $payload JSON data
     *
     * @param int $timeout Connection timeout in seconds.
     *
     * @return array
     */
    public function createCurlOptions(array $payload, $timeout = 10)
    {
        return [
            'connect_timeout' => $timeout,
            'headers'         => array_merge(
                [
                    'UNLEASH-APPNAME'    => $this->appName,
                    'UNLEASH-INSTANCEID' => $this->instanceId,
                    'User-Agent'         => $this->appName,
                ],
                $this->headers
            ),
            'json'            => $payload,
        ];
    }
}
