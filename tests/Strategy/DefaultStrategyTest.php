<?php

namespace Tests\Strategy;

use PHPUnit\Framework\TestCase;

class DefaultStrategyTest extends TestCase
{
    public function testDefaultStrategyShouldBeEnabled()
    {
        $strategy = new \Unleash\Strategy\DefaultStrategy();
        $this->assertTrue($strategy->isEnabled());
    }

    public function testShouldHaveCorrectName()
    {
        $strategy = new \Unleash\Strategy\ApplicationHostnameStrategy();
        $this->assertEquals('applicationHostname', $strategy->name);
    }
}