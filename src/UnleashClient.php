<?php

namespace Unleash;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Unleash\Events\WarnEvent;
use Unleash\Strategy\Strategy;
use Unleash\Strategy\StrategyTransportInterface;

class UnleashClient extends EventDispatcher
{
    private $repository;
    private $strategies;
    private $warned = [];

    /**
     * UnleashClient constructor.
     * @param Repository $repository
     * @param Strategy[] $strategies
     */
    public function __construct(Repository $repository, array $strategies = [])
    {
        $this->repository = $repository;
        $this->strategies = $strategies;

        foreach ($strategies as $strategy) {
            if (!$strategy instanceof Strategy) {
                throw new \Exception('Invalid strategy data / interface');
            }
        }
    }

    private function getStrategy(string $name): ?Strategy
    {
        $match = null;
        foreach ($this->strategies as $strategy) {
            if ($strategy->name === $name) {
                $match = $strategy;
                break;
            }
        }

        return $match;
    }

    /**
     * @param string $missingStrategy
     * @param string $name
     * @param StrategyTransportInterface[] $strategies
     */
    public function warnOnce(string $missingStrategy, string $name, array $strategies)
    {
        if (!isset($this->warned[$missingStrategy . $name])) {
            $this->warned[$missingStrategy . $name] = true;

            $strategyNames = implode(
                ", ",
                array_map(
                    function (StrategyTransportInterface $strategy) {
                        return $strategy->name;
                    },
                    $strategies
                )
            );
            $this->dispatch('warn', new WarnEvent("Missing strategy " . $missingStrategy . " for toggle " . $name . ". Ensure that " . $strategyNames . " are supported before using this toggle"));
        }
    }

    public function isEnabled(string $name, Context $context = null, bool $fallbackValue = null): bool
    {
        $feature = $this->repository->getToggle($name);

        if ($feature === null && is_bool($fallbackValue)) {
            return $fallbackValue;
        }

        if ($feature === null || !$feature->enabled) {
            return false;
        }

        if (count($feature->strategies) === 0) {
            return $feature->enabled;
        }

        print_r($feature);

        foreach ($feature->strategies as $strategySelector) {
            $strategy = $this->getStrategy($strategySelector->name);
            if ($strategy === null) {
                $this->warnOnce($strategySelector->name, $name, $feature->strategies);
                continue;
            }

            return $strategy->isEnabled($strategySelector->parameters, $context);
        }

        return false;
    }

    public function initialize(array $options)
    {
        $instance = new Unleash();
        $instance->addListener('error', function () {
        });

        $instance->initialize(
            $options['appName'],
            $options['url'],
            $options['instanceId'],
            $options['refreshInterval'],
            $options['metricsInterval'],
            $options['disableMetrics'],
            $options['backupPath'],
            $options['stategies'],
            $options['customHeaders']
        );

        return $instance;
    }

    public function destroy()
    {
        throw new \Exception("Not implemented", 1);
    }

    public function getFeatureToggleDefinition($toggleName)
    {
        throw new \Exception("Not implemented", 1);
    }

    public function count($toggleName, $enabled)
    {
        throw new \Exception("Not implemented", 1);
    }
}
