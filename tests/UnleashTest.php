<?php

namespace Test;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit_Framework_Assert as Assert;
use PHPUnit\Framework\TestCase;
use Unleash\Events\CountEvent;
use Unleash\Events\ErrorEvent;
use Unleash\Events\RegisterEvent;
use Unleash\Events\SentEvent;
use Unleash\Events\WarnEvent;
use Unleash\Unleash;

class UnleashTest extends TestCase
{
    public function mockNetwork(array $toggles = null)
    {
        if ($toggles === null) {
            $toggles[] = [
                'name' => 'feature',
                'enabled' => true,
                'strategy' => 'default',
            ];
        }

        $mock = new MockHandler(
            [
                new Response(200, [], json_encode(['features' => $toggles])),
            ]
        );

        $client = new Client(['handler' => HandlerStack::create($mock)]);
        return $client;
    }

    public function testShouldHandleOldUrl()
    {
        $instance = new \Unleash\Unleash();
        $instance->addListener(
            'warn',
            function (WarnEvent $event) {
                $this->assertNotNull($event);
            }
        );

        $instance->initialize(
            'foo',
            'http://test.nl/client/features',
            null,
            0,
            0,
            true
        );

        $instance->destroy();
    }

    public function testShouldHandleUrlWithoutEndingSlash()
    {
        $baseUrl = 'http://ttest.app/api';
        $instance = new \Unleash\Unleash();
        $instance->addListener(
            'warn',
            function (WarnEvent $event) {
                $this->assertNotNull($event);
            }
        );

        $instance->initialize(
            'foo',
            $baseUrl,
            null,
            0,
            0,
            true
        );

        $this->assertAttributeEquals($baseUrl . '/', 'url', Assert::readAttribute($instance, 'repository'));
    }

    public function testReEmitErrorForRepoStorageAndMetrics()
    {
        $baseUrl = 'http://ttest.app/api';
        $instance = new \Unleash\Unleash();
        $amountOfErrors = 0;
        $instance->addListener(
            'error',
            function (ErrorEvent $event) use (&$amountOfErrors) {
                $this->assertNotNull($event);
                $amountOfErrors++;
            }
        );

        $instance->initialize(
            'foo',
            $baseUrl,
            null,
            0,
            0,
            true
        );
        Assert::readAttribute($instance, 'repository')->dispatch('error', new ErrorEvent(['message' => 'error']));
        Assert::readAttribute($instance, 'metrics')->dispatch('error', new ErrorEvent(['message' => 'error']));
        Assert::readAttribute(Assert::readAttribute($instance, 'repository'), 'storage')->dispatch(
            'error',
            new ErrorEvent(
                ['message' => 'error']
            )
        );

        $this->assertEquals(3, $amountOfErrors);
    }

    public function testReEmitEventsFromRepositoryAndMetrics()
    {
        $baseUrl = 'http://ttest.app/api';
        $instance = new \Unleash\Unleash();
        $amountOfEvents = 0;
        $instance->addListener(
            'warn',
            function (WarnEvent $event) use (&$amountOfEvents) {
                $this->assertNotNull($event);
                $amountOfEvents++;
            }
        );
        $instance->addListener(
            'sent',
            function (SentEvent $event) use (&$amountOfEvents) {
                $this->assertNotNull($event);
                $amountOfEvents++;
            }
        );
        $instance->addListener(
            'registered',
            function (RegisterEvent $event) use (&$amountOfEvents) {
                $this->assertNotNull($event);
                $amountOfEvents++;
            }
        );
        $instance->addListener(
            'count',
            function (CountEvent $event) use (&$amountOfEvents) {
                $this->assertNotNull($event);
                $amountOfEvents++;
            }
        );

        $instance->initialize(
            'foo',
            $baseUrl,
            null,
            0,
            0,
            true
        );
        Assert::readAttribute($instance, 'repository')->dispatch('warn', new WarnEvent([]));
        Assert::readAttribute($instance, 'metrics')->dispatch('warn', new WarnEvent([]));
        Assert::readAttribute($instance, 'metrics')->dispatch('sent', new SentEvent([]));
        Assert::readAttribute($instance, 'metrics')->dispatch('registered', new RegisterEvent([]));
        Assert::readAttribute($instance, 'metrics')->dispatch('count', new CountEvent('test', true));

        $this->assertEquals(5, $amountOfEvents);
    }

    public function testRepositoryShouldSurfaceErrorWhenInvalidBasePath()
    {
        $baseUrl = 'http://unleash-surface.app//client/features';
        $instance = new \Unleash\Unleash();

        $amountOfEvents = 0;
        $instance->addListener(
            'error',
            function (ErrorEvent $event) use (&$amountOfEvents) {
                $this->assertContains(
                    'failed to open stream: No such file or directory',
                    $event->getError()['message']
                );
                $amountOfEvents++;
            }
        );

        $mock = new MockHandler(
            [
                new Response(200, [], json_encode(['features' => []])),
            ]
        );

        $client = new Client(['handler' => HandlerStack::create($mock)]);
        $backupPath = sys_get_temp_dir() . '/test-tmp';

        $instance->initialize(
            'foo',
            $baseUrl,
            null,
            0,
            0,
            true,
            $backupPath,
            [],
            [],
            $client
        );
        $instance->fetch();

        $this->assertEquals(1, $amountOfEvents);
    }

    public function testShouldAllowRequestEvenBeforeUnleashIsInitialized()
    {
        $client = $this->mockNetwork();
        $instance = new Unleash();
        $instance->initialize(
            'foo',
            'http://testurl.com',
            null,
            0,
            0,
            true,
            '',
            [],
            [],
            $client
        );
        $instance->fetch();
        $this->assertFalse($instance->isEnabled('unknown'));
        $instance->destroy();
    }

    public function testShouldConsiderKnownFeatureToggleAsActive()
    {
        $client = $this->mockNetwork();
        $instance = new Unleash();
        $amountOfEvents = 0;
        $instance->addListener(
            'ready',
            function () use ($instance, &$amountOfEvents) {
                $this->assertTrue($instance->isEnabled('feature'));
                $instance->destroy();
                $amountOfEvents++;
            }
        );
        $instance->initialize(
            'foo',
            'url',
            null,
            0,
            0,
            true,
            '',
            [],
            [],
            $client
        );
        $instance->fetch();

        $this->assertEquals(1, $amountOfEvents);
    }

    public function testShouldConsiderUnknownFeatureToggleAsDisabled()
    {
        $client = $this->mockNetwork();
        $instance = new Unleash();
        $amountOfEvents = 0;
        $instance->addListener(
            'ready',
            function () use ($instance, &$amountOfEvents) {
                $this->assertFalse($instance->isEnabled('unknown'));
                $instance->destroy();
                $amountOfEvents++;
            }
        );

        $instance->initialize(
            'foo',
            'url',
            null,
            0,
            0,
            true,
            '',
            [],
            [],
            $client
        );
        $instance->fetch();

        $this->assertEquals(1, $amountOfEvents);
    }

    public function testShouldReturnFallbackValueUntilOnline()
    {
        $client = $this->mockNetwork();
        $instance = new Unleash();
        $amountOfEvents = 0;
        $amountOfWarnings = 0;
        $instance->addListener(
            'warn',
            function (WarnEvent $event) use (&$amountOfEvents, &$amountOfWarnings) {
                var_dump($event);
                $amountOfEvents++;
                $amountOfWarnings++;
            }
        );
        $instance->addListener(
            'ready',
            function () use (&$amountOfEvents, $instance) {
                $amountOfEvents++;
                $this->assertTrue($instance->isEnabled('feature'));
                $this->assertTrue($instance->isEnabled('feature', null, false));
                $instance->destroy();
            }
        );

        $instance->initialize(
            'foo',
            'http://url.com',
            null,
            0,
            0,
            true,
            '',
            [],
            [],
            $client
        );

        $this->assertFalse($instance->isEnabled('feature'));
        $this->assertEquals(1, $amountOfWarnings);
        $this->assertFalse($instance->isEnabled('feature', null, false));
        $this->assertTrue($instance->isEnabled('feature', null, true));
        $this->assertEquals(3, $amountOfWarnings);

        $instance->fetch();

        $this->assertEquals(4, $amountOfEvents);
    }
}
