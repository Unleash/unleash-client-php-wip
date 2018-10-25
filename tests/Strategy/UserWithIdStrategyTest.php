<?php

namespace Test\Strategy;

use PHPUnit\Framework\TestCase;
use Unleash\Context;
use Unleash\Strategy\UserWithIdStrategy;

class UserWithIdStrategyTest extends TestCase
{
    public function testCorrectName()
    {
        $strategy = new UserWithIdStrategy();
        $this->assertEquals('userWithId', $strategy->name);
    }

    public function testEnabledForUserId()
    {
        $strategy = new UserWithIdStrategy();
        $params = ['userIds' => '123'];
        $context = new Context();
        $context->userId = '123';

        $this->assertTrue($strategy->isEnabled($params, $context));
    }

    public function testEnabledForUserIdInList()
    {
        $strategy = new UserWithIdStrategy();
        $params = ['userIds' => '123, 122, 12312312'];
        $context = new Context();
        $context->userId = '12312312';

        $this->assertTrue($strategy->isEnabled($params, $context));
    }

    public function testNotEnabledForUserIdNotInList()
    {
        $strategy = new UserWithIdStrategy();
        $params = ['userIds' => '123, 122, 122'];
        $context = new Context();
        $context->userId = '12';

        $this->assertFalse($strategy->isEnabled($params, $context));
    }

    public function testEnabledForUserIdInListNoSpaces()
    {
        $strategy = new UserWithIdStrategy();
        $params = ['userIds' => '123, 122, 12312312'];
        $context = new Context();
        $context->userId = '122';

        $this->assertTrue($strategy->isEnabled($params, $context));
    }
}