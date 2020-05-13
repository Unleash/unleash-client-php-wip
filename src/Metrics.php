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
use Unleash\Strategy\Strategy;

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

    private $started;

    private $headers;

    private $client;

    const DATE_ISO_8601 = "Y-m-d\TH:i:sO";

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
        $this->sdkVersion = '1.0';//@todo: get the correct version
        $this->strategies = $strategies;
        $this->url = $url;
        $this->headers = $headers;
        $this->started = new \DateTime();
        if ($client === null) {
            $client = new Client(
                [
                    'base_uri' => $url,
                ]
            );
        }
        $this->client = $client;

        $this->resetBucket();
    }

    public function init()
    {
        if ($this->metricsInterval > 0) {
            $this->registerInstance();
            $this->sendMetrics();
            register_shutdown_function(
                function () {
                    $this->sendMetrics();
                }
            );
        }
    }

    public function startTimer()
    {
        $this->disabled = $this->metricsInterval < 1;
        return !$this->disabled;
    }

    public function stop()
    {
        $this->disabled = true;
    }

    public function registerInstance()
    {
        if ($this->disabled) {
            return false;
        }

        $payload = $this->getClientData();
        $options = $this->createCurlOptions($payload);
        $url = './client/register';

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
            return true;
        }

        $payload = $this->getPayload();
        $options = $this->createCurlOptions($payload);
        $url = './client/metrics';
        try {
            $response = $this->client->request('post', $url, $options);
        } catch (ClientException $exception) {
            if ($exception->getResponse()->getStatusCode() === 404) {
                $this->dispatch('warn', new WarnEvent($url . ' returning 404, stopping metrics'));
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
                'no' => 0,
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
        $bucket->start = (new \DateTime())->format(self::DATE_ISO_8601);
        $bucket->stop = null;
        $bucket->toggles = [];
        $this->bucket = $bucket;
    }

    public function closeBucket()
    {
        $this->bucket->stop = (new \DateTime())->format(self::DATE_ISO_8601);
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
            'appName' => $this->appName,
            'instanceId' => $this->instanceId,
            'sdkVersion' => $this->sdkVersion,
            'strategies' => array_map(
                function (Strategy $s) {
                    return $s->name;
                },
                $this->strategies
            ),
            'interval' => $this->metricsInterval,
            'started' => (new \DateTime())->format(self::DATE_ISO_8601),
        ];
    }

    public function getMetricsData()
    {
        return [
            'appName' => $this->appName,
            'instanceId' => $this->instanceId,
            'bucket' => (array)$this->bucket,
        ];
    }

    /**
     * @param array $payload JSON data
     *
     * @param int   $timeout Connection timeout in seconds.
     *
     * @return array
     */
    public function createCurlOptions(array $payload, $timeout = 10)
    {
        return [
            'connect_timeout' => $timeout,
            'headers' => array_merge(
                [
                    'UNLEASH-APPNAME' => $this->appName,
                    'UNLEASH-INSTANCEID' => $this->instanceId,
                    'User-Agent' => $this->appName,
                ],
                $this->headers
            ),
            'json' => $payload,
        ];
    }
}
