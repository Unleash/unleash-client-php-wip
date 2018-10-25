<?php

namespace Test\Strategy;

use PHPUnit\Framework\TestCase;
use Unleash\Context;
use Unleash\Strategy\RemoteAddressStrategy;

class RemoteAddressStrategyTest extends TestCase
{
    public function testCorrectName()
    {
        $strategy = new RemoteAddressStrategy();
        $this->assertEquals('remoteAddress', $strategy->name);
    }

    public function testNotCrashForMissingParams()
    {
        $strategy = new RemoteAddressStrategy();
        $context = new Context();
        $context->remoteAddress = '123';

        $this->assertFalse($strategy->isEnabled([], $context));
    }

    public function testEnabledForIpInSingleList()
    {
        $strategy = new RemoteAddressStrategy();
        $params = ['IPs' => '127.0.0.1'];
        $context = new Context();
        $context->remoteAddress = '127.0.0.1';

        $this->assertTrue($strategy->isEnabled($params, $context));
    }

    public function testNotEnabledForIpNotInList()
    {
        $strategy = new RemoteAddressStrategy();
        $params = ['IPs' => '127.0.1.1, 127.0.1.2, 127.0.1.3'];
        $context = new Context();
        $context->remoteAddress = '127.0.1.5';

        $this->assertFalse($strategy->isEnabled($params, $context));
    }

    public function testEnabledForIpInList()
    {
        $strategy = new RemoteAddressStrategy();
        $params = ['IPs' => '127.0.1.1, 127.0.1.2,127.0.1.3'];
        $context = new Context();
        $context->remoteAddress = '127.0.1.2';

        $this->assertTrue($strategy->isEnabled($params, $context));
    }

    public function testEnabledForIpInsideRangeInAList()
    {
        $strategy = new RemoteAddressStrategy();
        $params = ['IPs' => '127.0.1.1, 127.0.1.2,127.0.1.3, 160.33.0.0/16'];
        $context = new Context();
        $context->remoteAddress = '160.33.0.33';

        $this->assertTrue($strategy->isEnabled($params, $context));
    }

    public function testInvalidIp()
    {
        $strategy = new RemoteAddressStrategy();
        $params = ['IPs' => '127.invalid'];
        $context = new Context();
        $context->remoteAddress = '127.0.0.1';

        $this->assertFalse($strategy->isEnabled($params, $context));
    }

    public function testInvalidIps()
    {
        $strategy = new RemoteAddressStrategy();
        $params = ['IPs' => '127.0.0.2, 127.invalid, 127.0.0.1'];
        $context = new Context();
        $context->remoteAddress = '127.0.0.1';

        $this->assertTrue($strategy->isEnabled($params, $context));
    }
}