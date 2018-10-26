<?php

namespace Unleash;

use Symfony\Component\EventDispatcher\EventDispatcher;
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
                    function (Strategy $strategy) {
                        return $strategy->name;
                    },
                    $strategies
                )
            );
            $this->dispatch('warn',
                "Missing strategy " . $missingStrategy . " for toggle " . $name . ". Ensure that " . $strategyNames . " are supported before using this toggle");
        }
    }

    public function isEnabled(string $name, Context $context, bool $fallbackValue = false): bool
    {
        $feature = $this->repository->getToggle($name);

        if($feature === null){
            return $fallbackValue;
        }

        if(!$feature->enabled){
            return false;
        }

        if(count($feature->strategies) === 0){
            return $feature->enabled;
        }

        foreach ($feature->strategies as $strategySelector){
            $strategy = $this->getStrategy($strategySelector->name);
            if($strategy === null){
                $this->warnOnce($strategySelector->name, $name, $feature->strategies);
                continue;
            }

            return $strategy->isEnabled($strategySelector->parameters, $context);
        }

        return $fallbackValue;
    }

    public function initialize($options)
    {
        throw new \Exception("Not implemented", 1);

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
