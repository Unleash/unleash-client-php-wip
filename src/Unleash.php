<?php

namespace Unleash;

use GuzzleHttp\Client;
use Sabre\Event\EventEmitter;
use Sabre\Event\Loop\Loop;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Unleash\Events\CountEvent;
use Unleash\Events\ErrorEvent;
use Unleash\Events\RegisterEvent;
use Unleash\Events\SentEvent;
use Unleash\Events\WarnEvent;
use Unleash\Strategy\ApplicationHostnameStrategy;
use Unleash\Strategy\DefaultStrategy;
use Unleash\Strategy\GradualRolloutRandomStrategy;
use Unleash\Strategy\GradualRolloutSessionIdStrategy;
use Unleash\Strategy\GradualRolloutUserIdStrategy;
use Unleash\Strategy\RemoteAddressStrategy;
use Unleash\Strategy\Strategy;
use Unleash\Strategy\UserWithIdStrategy;

class Unleash extends EventDispatcher
{
    /** @var Repository */
    private $repository;
    /** @var UnleashClient */
    private $client;
    /** @var Metrics */
    private $metrics;

    /**
     * @param string $appName
     * @param string $url
     * @param string $instanceId
     * @param int $refreshInterval
     * @param int $metricsInterval
     * @param bool $disableMetrics
     * @param string $backupPath
     * @param Strategy[] $strategies
     * @param array $customHeaders
     * @param Client|null $client
     */
    public function initialize(
        $appName,
        $url,
        $instanceId = null,
        $refreshInterval = 15 * 1000,
        $metricsInterval = 50 * 1000,
        $disableMetrics = false,
        $backupPath = '',//@todo: should be a tmp directory
        array $strategies = [],
        array $customHeaders = [],
        Client $client = null
    ) {
        if (substr($url, -9) === '/features') {
            $oldUrl = $url;
            $err = ['message' => 'Unleash server URL ' . $oldUrl . ' should no longer link directly to /features'];
            $event = new WarnEvent($err);
            $this->dispatch('warn', $event);
            $url = str_replace('/features', '', $url);
        }

        if (substr($url, -1) !== '/') {
            $url .= '/';
        }

        if ($instanceId === null) {
            $info = posix_getpwuid(posix_geteuid());

            $prefix = $info['username'] ?? 'generated-' . round((mt_rand() / mt_getrandmax() * 1000000)) . '-' . getmypid();
            $instanceId = $prefix . '-' . $_SERVER['host_name'];
        }

        $this->repository = new Repository(
            $backupPath,
            $url,
            $appName,
            $instanceId,
            $refreshInterval,
            $customHeaders,
            null,
            $client
        );

        $defaultStrategies = [
            new DefaultStrategy(),
            new ApplicationHostnameStrategy(),
            new GradualRolloutRandomStrategy(),
            new GradualRolloutUserIdStrategy(),
            new GradualRolloutSessionIdStrategy(),
            new UserWithIdStrategy(),
            new RemoteAddressStrategy(),
        ];

        $strategies = array_merge($defaultStrategies, $strategies);
        $this->repository->addListener('ready', function () use ($strategies) {
            $this->client = new UnleashClient($this->repository, $strategies);
            $this->client->addListener('error', function (ErrorEvent $event) {
                $this->dispatch('error', $event);
            });
            $this->client->addListener('warn', function (WarnEvent $event) {
                $this->dispatch('warn', $event);
            });
            $this->dispatch('ready');
        });

        $this->repository->addListener('error', function (ErrorEvent $event) {
            $event->getError()['message'] = 'Unleash Repository error: ' . $event->getError()['message'];
            $this->dispatch('error', $event);
        });

        $this->repository->addListener('warn', function (WarnEvent $event) {
            $this->dispatch('warn', $event);
        });

        $this->metrics = new Metrics(
            $appName,
            $instanceId,
            $strategies,
            $url,
            $customHeaders,
            $metricsInterval,
            $disableMetrics,
            $this->client
        );

        $this->metrics->addListener('error', function (ErrorEvent $event) {
            $event->setError('Unleash Metrics error: ' . $event->getError()['message']);
            $this->dispatch('error', $event);
        });

        $this->metrics->addListener('warn', function (WarnEvent $event) {
            $this->dispatch('warn', $event);
        });

        $this->metrics->addListener('count', function (CountEvent $event) {
            $this->dispatch('count', $event);
        });

        $this->metrics->addListener('sent', function (SentEvent $event) {
            $this->dispatch('sent', $event);
        });

        $this->metrics->addListener('registered', function (RegisterEvent $event) {
            $this->dispatch('registered', $event);
        });
    }

    public function fetch()
    {
        $this->repository->fetch();
    }

    public function isEnabled(string $name, Context $context = null, bool $fallbackValue = null)
    {
        $result = null;
        if ($this->client !== null) {
            $result = $this->client->isEnabled($name, $context, $fallbackValue);
        } else {
            $result = is_bool($fallbackValue) ? $fallbackValue : false;
            $this->dispatch(
                'warn',
                new WarnEvent('Unleash has not been initialized yet. isEnabled(' . $name . ') defaulted to ' . $fallbackValue)
            );
        }
        $this->count($name, $result);
        return $result;
    }

    public function count(string $toggleName, bool $enabled)
    {
        $this->metrics->count($toggleName, $enabled);
    }

    public function destroy()
    {
        $this->repository->stop();
        $this->metrics->stop();
        $this->client = null;
    }
}
