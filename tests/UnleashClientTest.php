<?php

namespace Test;

use PHPUnit\Framework\TestCase;
use Unleash\Context;
use Unleash\Events\WarnEvent;
use Unleash\Feature;
use Unleash\Repository;
use Unleash\Strategy\Strategy;
use Unleash\UnleashClient;

class UnleashClientTest extends TestCase
{
    public function testInvalidStrategyShouldBeThrown()
    {
        $repo = new Repository('', '', '', '');
        $amountOfExceptions = 0;
        try {
            new UnleashClient($repo, [true, null]);
        } catch (\Exception $e) {
            $amountOfExceptions++;
        }

        try {
            new UnleashClient($repo, [[]]);
        } catch (\Exception $e) {
            $amountOfExceptions++;
        }

        $this->assertEquals(2, $amountOfExceptions);
    }

    public function testShouldUseProvideRepository()
    {
        $repo = $this->createMock(Repository::class);
        $repo->method('getToggle')
            ->willReturn($this->createFeature('feature', true));
        $client = new UnleashClient($repo, [new Strategy('default', true)]);
        $result = $client->isEnabled('feature');

        $this->assertTrue($result);
    }

    public function testShouldFallbackWhenMissingFeature()
    {
        $repo = $this->createMock(Repository::class);
        $repo->method('getToggle')
            ->willReturn(null);
        $client = new UnleashClient($repo, [new Strategy('default', true)]);
        $result = $client->isEnabled('feature-x', null);
        $this->assertFalse($result);
        $result = $client->isEnabled('feature-x', null, true);
        $this->assertTrue($result);
    }

    public function testShouldConsiderToggleNotActive()
    {
        $repo = $this->createMock(Repository::class);
        $repo->method('getToggle')
            ->willReturn($this->createFeature('feature', false));
        $client = new UnleashClient($repo, [new Strategy('default', true)]);
        $result = $client->isEnabled('feature');
        $this->assertFalse($result);
    }

    public function testShouldUserCustomStrategy()
    {
        $repo = $this->createMock(Repository::class);
        $repo->method('getToggle')
            ->willReturn($this->createFeature('feature', true, [new Strategy('custom')]));
        $client = new UnleashClient($repo, [new Strategy('default', true), new CustomStrategy()]);
        $result = $client->isEnabled('feature');
        $this->assertTrue($result);
    }

    public function testShouldUseASetOfCustomStrategies()
    {
        $repo = $this->createMock(Repository::class);
        $repo->method('getToggle')
            ->willReturn($this->createFeature('feature', true, [new Strategy('custom-false'), new Strategy('custom')]));
        $strategies = [new CustomFalseStrategy(), new CustomStrategy()];
        $client = new UnleashClient($repo, $strategies);
        $result = $client->isEnabled('feature');

        $this->assertTrue($result);
    }

    public function testShouldReturnFalseASetOfCustomFalseStrategies()
    {
        $repo = $this->createMock(Repository::class);
        $repo->method('getToggle')
            ->willReturn($this->createFeature('feature', true, [
                new Strategy('custom-false'),
                new Strategy('custom-false'),
            ]));

        $strategies = [new CustomFalseStrategy(), new CustomStrategy()];
        $client = new UnleashClient($repo, $strategies);
        $result = $client->isEnabled('feature');

        $this->assertFalse($result);
    }

    public function testShouldEmitErrorWhenInvalidFeatureRuntime()
    {
        $repo = $this->createMock(Repository::class);
        $repo->method('getToggle')
            ->willReturn($this->createFeature('feature-wrong-strategy', true, [new Strategy('non-existent')]));
        $client = new UnleashClient($repo, []);
        $amountOfEvents = 0;
        $client->addListener('warn', function (WarnEvent $warnEvent) use (&$amountOfEvents){
            $this->assertContains('Missing strategy', $warnEvent->getMessage());
            $amountOfEvents++;
        });

        $this->assertFalse($client->isEnabled('feature-strong-strategy'));
        $this->assertEquals(1, $amountOfEvents);
    }

    public function createFeature(string $name, bool $enabled, array $strategies = [])
    {
        if (empty($strategies)) {
            $strategies = [
                new Strategy('default'),
            ];
        }

        $feature = new Feature($name, $enabled);
        $feature->strategies = $strategies;

        return $feature;
    }
}

class CustomStrategy extends Strategy
{
    public function __construct(string $name = 'unknown', bool $returnValue = false)
    {
        parent::__construct('custom');
    }

    public function isEnabled(array $parameters = null, Context $context = null): bool
    {
        return true;
    }
}

class CustomFalseStrategy extends Strategy
{
    public function __construct(string $name = 'unknown', bool $returnValue = false)
    {
        parent::__construct('custom-false');
    }

    public function isEnabled(array $parameters = null, Context $context = null): bool
    {
        return false;
    }
}