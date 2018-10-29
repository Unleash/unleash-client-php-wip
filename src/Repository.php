<?php

namespace Unleash;

use GuzzleHttp\Client;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Unleash\Events\ErrorEvent;
use Unleash\Strategy\StrategyTransportInterface;

class Repository extends EventDispatcher
{
    private $url;
    private $refreshInterval;
    private $instanceId;
    private $appName;
    private $headers;
    private $storage;
    private $client;
    private $etag;

    public function __construct(
        string $backupPath,
        string $url,
        string $appName,
        string $instanceId,
        string $refreshInterval = null,
        array $headers = [],
        Storage $storageImpl = null,
        Client $client = null
    ) {
        if ($client === null) {
            $this->client = new Client([
                'base_uri' => $url,
            ]);
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

        $this->storage->addListener('error', function (ErrorEvent $event) {
            $this->dispatch('error', $event);
        });
        $this->storage->addListener('ready', function () {
            $this->dispatch('ready');
        });
    }

    public function fetch()
    {
        $url = './client/features';
        $options = [
            'connect_timeout' => 1,//@todo: 10000 in Node we need to check how long this is seconds.
            'headers'         => array_merge(
                [
                    'UNLEASH-APPNAME'    => $this->appName,
                    'UNLEASH-INSTANCEID' => $this->instanceId,
                    'User-Agent'         => $this->appName,
                ],
                $this->headers
            ),
        ];

        if (!empty($this->etag)) {
            $options['If-None-match'] = $this->etag;
        }
        $response = $this->client->get($url, $options);
        if ($response->getStatusCode() === 304) {
            return;
        }

        if (!($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            return $this->dispatch('error',
                new ErrorEvent(['message' => 'Response was not statusCode 2XX, but was ' . $response->getStatusCode()]));
        }

        $payload = json_decode($response->getBody()->getContents(), true);
        $features = $this->pickData($payload)['features'];

        $this->storage->reset($features);
        $this->etag = $response->getHeader('etag');
        $this->dispatch('data');
    }

    function pickData(array $data): array
    {
        $features = [];
        foreach ($data['features'] as $row) {
            $feature = new Feature();
            $feature->name = $row['name'];
            $feature->enabled = $row['enabled'];
            $feature->strategies = [new StrategyTransportInterface($row['strategy'], $row['parameters'])];
            $features[] = $feature;
        }

        return [
            'version'  => 1,
            'features' => $features,
        ];
    }

    public function getToggle(string $name): ?FeatureInterface
    {
        return $this->storage->get($name);
    }

    public function stop()
    {
        //@todo: implement
    }
}