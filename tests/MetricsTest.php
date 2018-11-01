<?php

namespace Test;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Unleash\Bucket;
use Unleash\Events\ErrorEvent;
use Unleash\Metrics;

class MetricsTest extends TestCase
{
    public function mockNetwork($statuscode = 200)
    {
        $mock = new MockHandler([
            new Response($statuscode),
            new Response($statuscode),
        ]);

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
        $this->markTestSkipped('broken, needs fix');
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
        $this->markTestSkipped('broken, needs fix');
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

    public function testShouldSendCustomHeaders()
    {
        $this->markTestSkipped('broken, needs fix');
        $randomKey = 'value-' . mt_rand(1,100);

        $client = \Mockery::mock(Client::class);
        $client->shouldReceive('request')
            ->withArgs(function ($method, $url, $options) use ($randomKey){
                $this->assertEquals($randomKey, $options['headers']['randomKey']);
                return isset($options['headers']['randomKey']) ? true : false;
            })
            ->andReturn(new Response());
        $metrics = new Metrics('', '', [], '', [
            'randomKey' => $randomKey
        ], 0.05, false, $client);
        $metrics->count('toggle-x', true);
        $metrics->count('toggle-x', false);
        $metrics->count('toggle-y', true);

        $eventCount = 0;
        $metrics->addListener('sent', function () use (&$eventCount) {
            $eventCount++;
        });

        $metrics->init();
        $this->assertEquals(1, $eventCount);
    }

    public function testRegisterInstanceShouldWarnWhenNon200StatusCode()
    {
        $client = $this->mockNetwork(500);
        $metrics = new Metrics('', '', [], '', [], 0, false, $client);
        $eventCount = 0;
        $metrics->addListener('error', function (ErrorEvent $event) use (&$eventCount){
            $this->assertNotNull($event);
            $eventCount++;
        });

        $metrics->init();

        //@todo: This test might not be correct
        $this->assertFalse($metrics->registerInstance());
        $this->assertEquals(1, $eventCount);
    }

    public function testSendMatricsShouldStopDisableMetricsIfEndpointReturn404()
    {
        $client = $this->mockNetwork(404);
        $metrics = new Metrics('', '', [], '', [], 0, false, $client);
        $eventCount = 0;
        $metrics->addListener('warn', function () use (&$eventCount, $metrics){
            $metrics->stop();
            $this->assertTrue(Assert::readAttribute($metrics, 'disabled'));
            $eventCount++;
        });
        $metrics->init();

        $metrics->count('x-y-z', true);
        $metrics->sendMetrics();
        //@todo: This test might not be correct
        $this->assertTrue(Assert::readAttribute($metrics, 'disabled'));
    }

    public function testSendMetricsShouldEmitWarnOnNon200StatusCode()
    {
        $client = $this->mockNetwork(406);
        $metrics = new Metrics('', '', [], '', [], 0, false, $client);
        $eventCount = 0;
        $metrics->addListener('warn', function () use (&$eventCount, $metrics){
            $eventCount++;
        });
        $metrics->init();

        $metrics->count('x-y-z', true);
        $metrics->sendMetrics();
        $this->assertEquals(1, $eventCount);
    }

    public function testSendMetricsShouldNotSendEmptyBuckets()
    {
        $this->markTestSkipped('Test is broken atm, needs fix');

        $client = $this->mockNetwork(200);
        $metrics = new Metrics('', '', [], '', [], 0, false, $client);
        $metrics->init();

        //@todo:
        $this->assertTrue($metrics->sendMetrics());
    }

    public function testCountShouldIncrementYesAndNoCounters()
    {
        $client = $this->mockNetwork();
        $metrics = new Metrics('', '', [], '', [], 0, false, $client);
        $metrics->init();

        $name = 'name' . mt_rand(0,10000);
        $this->assertFalse(isset(Assert::readAttribute($metrics, 'bucket')->toggles[$name]));

        $metrics->count($name, true);

        $this->assertEquals(1,Assert::readAttribute($metrics, 'bucket')->toggles[$name]['yes']);
        $this->assertEquals(0,Assert::readAttribute($metrics, 'bucket')->toggles[$name]['no']);

        $metrics->count($name, true);
        $metrics->count($name, true);
        $metrics->count($name, false);
        $metrics->count($name, false);
        $metrics->count($name, false);
        $metrics->count($name, false);

        $this->assertEquals(3,Assert::readAttribute($metrics, 'bucket')->toggles[$name]['yes']);
        $this->assertEquals(4,Assert::readAttribute($metrics, 'bucket')->toggles[$name]['no']);
    }
}