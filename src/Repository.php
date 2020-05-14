<?php

namespace Unleash;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Unleash\Events\ErrorEvent;
use Unleash\Strategy\StrategyTransportInterface;

class Repository extends EventDispatcher
{
    private $timer;

    private $url;

    private $refreshInterval;

    private $instanceId;

    private $appName;

    private $headers;

    private $storage;

    private $client;

    private $etag = 'unknown';

    public function __construct(
        $backupPath,
        $url,
        $appName,
        $instanceId,
        $refreshInterval = null,
        array $headers = [],
        Storage $storageImpl = null,
        Client $client = null
    ) {
        if ($client === null) {
            $this->client = new Client(
                [
                    'base_uri' => $url,
                ]
            );
        } else {
            $this->client = $client;
        }
        $this->url = $url;
        $this->refreshInterval = $refreshInterval;
        $this->instanceId = $instanceId;
        $this->appName = $appName;
        $this->headers = $headers;

        if ($storageImpl === null) {
            $this->storage = new Storage($backupPath, $appName);
        } else {
            $this->storage = $storageImpl;
        }

        $this->storage->addListener(
            'error',
            function (ErrorEvent $event) {
                $this->dispatch('error', $event);
            }
        );
        $this->storage->addListener(
            'ready',
            function () {
                $this->dispatch('ready');
            }
        );
    }

    public function fetch()
    {
        $url = './client/features';
        $options = $this->createOptions();
        try {
            $response = $this->client->get($url, $options);
        } catch (ClientException $exception) {
            $response = $exception->getResponse();
        } catch (ServerException $exception) {
            $this->dispatch('error', new ErrorEvent(['message' => $exception->getMessage()]));
            return false;
        }

        if ($response->getStatusCode() === 304) {
            return;
        }

        if (!($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            return $this->dispatch(
                'error',
                new ErrorEvent(['message' => 'Response was not statusCode 2XX, but was ' . $response->getStatusCode()])
            );
        }

        $payload = json_decode($response->getBody()->getContents(), true);
        $features = $this->pickData($payload)['features'];

        $this->storage->reset($features);
        $this->etag = $response->getHeader('etag');
        $this->dispatch('data');
    }

    public function pickData(array $data)
    {
        $features = [];
        foreach ($data['features'] as $row) {
            $feature = new Feature();
            $feature->name = $row['name'];
            $feature->enabled = $row['enabled'];
            $feature->description = $row['description'];
            $feature->strategies = $this->mapToStrategies($row['strategies']);
            $features[$row['name']] = $feature;
        }

        return [
            'version' => 1,
            'features' => $features,
        ];
    }

    protected function mapToStrategies(array $rawStrategies = null)
    {
        $arr = [];
        foreach ((array)$rawStrategies as $row) {
            $arr[] = new StrategyTransportInterface(
                $row['name'], isset($row['parameters']) ? $row['parameters'] : null
            );
        }

        return $arr;
    }

    public function getToggle($name)
    {
        return $this->storage->get($name);
    }

    public function stop()
    {
        //@todo: implement
    }

    public function createOptions($timeout = 10)
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
            'If-None-match' => $this->etag,
        ];
    }
}
