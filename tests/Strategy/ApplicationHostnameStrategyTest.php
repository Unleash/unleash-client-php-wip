<?php

namespace Tests\Strategy;

use \PHPUnit\Framework\TestCase;

class ApplicationHostnameStrategyTest extends TestCase
{
    public function testCorrectName()
    {
        $strategy = new \Unleash\Strategy\ApplicationHostnameStrategy();
        $this->assertEquals('applicationHostname', $strategy->name);
    }

    public function testShouldBeDisabledWhenNoHostnameDefined()
    {
        $strategy = new \Unleash\Strategy\ApplicationHostnameStrategy();
        $this->assertFalse($strategy->isEnabled(['hostNames' => '']));
    }

    public function testShouldBeEnabledWhenHostnameIsDefined()
    {
        $_SERVER['host_name'] = '';
        $strategy = new \Unleash\Strategy\ApplicationHostnameStrategy();
        $this->assertTrue($strategy->isEnabled(['hostNames' => $_SERVER['host_name']]));
    }

    public function testShouldBeEnabledWhenHostnameIsDefinedInList()
    {
        $_SERVER['host_name'] = '';
        $strategy = new \Unleash\Strategy\ApplicationHostnameStrategy();
        $this->assertTrue($strategy->isEnabled(['hostNames' => 'localhost, '. $_SERVER['host_name']]));
    }

    public function testShouldBeEnabledWhenHostnameIsDefinedViaServerGlobal()
    {
        $_SERVER['host_name'] = 'some-random-name';
        $strategy = new \Unleash\Strategy\ApplicationHostnameStrategy();
        $this->assertTrue($strategy->isEnabled(['hostNames' => 'localhost, some-random-name']));
    }

    public function testShouldBeHandleWierdCasting()
    {
        $_SERVER['host_name'] = 'some-random-NAME';
        $strategy = new \Unleash\Strategy\ApplicationHostnameStrategy();
        $this->assertTrue($strategy->isEnabled(['hostNames' => 'localhost, some-random-name']));
    }
}