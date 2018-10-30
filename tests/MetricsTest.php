<?php

namespace Test;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Unleash\Bucket;
use Unleash\Metrics;

class MetricsTest extends TestCase
{
    public function mockNetwork(callable $onFulfilled = null, callable $onRejected = null)
    {
        $mock = new MockHandler([
            new Response(200),
            new Response(200),
        ], $onFulfilled, $onRejected);

        $client = new Client(['handler' => HandlerStack::create($mock)]);
        return $client;
    }

    public function testShouldBeDisabledByFlagDisableMetrics()
    {
        $metrics = new Metrics('', '', [], '', [], 0, true);
        $metrics->count('foo', true);
        /** @var Bucket $bucket */
        $bucket = Assert::readAttribute($metrics, 'bucket');

        $this->assertEquals(0, count($bucket->toggles));
    }

    public function testFunctionsShouldRespectDisabled()
    {
        $metrics = new Metrics('', '', [], '', [], 0, true);
        $this->assertFalse($metrics->startTimer());
        $this->assertFalse($metrics->registerInstance());
        $this->assertFalse($metrics->count());
        $this->assertFalse($metrics->sendMetrics());
    }

    public function testShouldNotStartOrRegisterWhenMetricsIntervalIs0()
    {
        $metrics = new Metrics('', '', [], '', [], 0, true);
        $timer = Assert::readAttribute($metrics, 'timer');
        $this->assertNull($timer);
    }

    public function testSendMetricsAndRegisterWhenMetricsIntervalIsAPositiveNumber()
    {
        $client = $this->mockNetwork();
        $metrics = new Metrics('', '', [], '', [], 0.05, false, $client);
        $metrics->count('toggle-x', true);
        $metrics->count('toggle-x', false);
        $metrics->count('toggle-y', true);

        $eventCount = 0;
        $metrics->addListener('registered', function () use (&$eventCount) {
            $eventCount++;
        });
        $metrics->addListener('sent', function () use (&$eventCount) {
            $eventCount++;
        });

        $metrics->init();
        $this->assertEquals(2, $eventCount);
    }

    public function testShouldSendMetrics()
    {
        $client = \Mockery::mock(Client::class);
        $client->shouldReceive('request')
            ->withArgs(function ($method, $url, $options){
                if($url === '/client/metrics'){
                    $this->assertNotNull($options['json']['bucket']['start']);
                    $this->assertNotNull($options['json']['bucket']['stop']);
                    $this->assertEquals([
                        'toggle-x' => ['yes' => 1, 'no' => 1],
                        'toggle-y' => ['yes' => 1, 'no' => 0]
                    ],$options['json']['bucket']['toggles']);
                }

                return true;
            })
            ->andReturn(new Response());
        $metrics = new Metrics('', '', [], '', [], 0.05, false, $client);
        $metrics->count('toggle-x', true);
        $metrics->count('toggle-x', false);
        $metrics->count('toggle-y', true);

        $eventCount = 0;
        $metrics->addListener('registered', function () use (&$eventCount) {
            $eventCount++;
        });
        $metrics->addListener('sent', function () use (&$eventCount) {
            $eventCount++;
        });

        $metrics->init();
        $this->assertEquals(2, $eventCount);
    }
}