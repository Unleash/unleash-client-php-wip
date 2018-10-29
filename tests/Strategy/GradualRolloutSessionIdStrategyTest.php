<?php

namespace Tests\Strategy;

use Unleash\Strategy\GradualRolloutSessionIdStrategy;

class GradualRolloutSessionIdStrategyTest extends \PHPUnit\Framework\TestCase
{
    public function testCorrectName()
    {
        $strategy = new GradualRolloutSessionIdStrategy();
        $this->assertEquals('gradualRolloutSessionId', $strategy->name);
    }

    public function testAlways100Percentage()
    {
        $strategy = new GradualRolloutSessionIdStrategy();
        $context = new \Unleash\Context();
        $context->sessionId = '123';
        $parameters = ['percentage' => 100, 'groupId' => 'gr1'];
        $this->assertTrue($strategy->isEnabled($parameters, $context));
    }

    public function testAlways0Percentage()
    {
        $strategy = new GradualRolloutSessionIdStrategy();
        $context = new \Unleash\Context();
        $context->sessionId = '123';
        $parameters = ['percentage' => 0, 'groupId' => 'gr1'];
        $this->assertFalse($strategy->isEnabled($parameters, $context));
    }

    public function testShouldBeEnabledWhenPercentageIsExactlySame()
    {
        $strategy = new GradualRolloutSessionIdStrategy();
        $context = new \Unleash\Context();

        $sessionId = '123123';
        $groupId = 'group1';

        $percentage = normalizeValue($sessionId, $groupId);
        $context->sessionId = $sessionId;
        $parameters = ['percentage' => $percentage, 'groupId' => $groupId];
        $this->assertTrue($strategy->isEnabled($parameters, $context));
    }

    public function testShouldBeDisabledWhenPercentageIsJustBelowRequiredValue()
    {
        $strategy = new GradualRolloutSessionIdStrategy();
        $context = new \Unleash\Context();

        $sessionId = '123123';
        $groupId = 'group1';

        $percentage = normalizeValue($sessionId, $groupId) - 1;
        $context->sessionId = $sessionId;
        $parameters = ['percentage' => $percentage, 'groupId' => $groupId];
        $this->assertFalse($strategy->isEnabled($parameters, $context));
    }

    public function testShouldOlyAtMostMissByOnePercent()
    {
        $strategy = new GradualRolloutSessionIdStrategy();

        $percentage = 25;
        $groupId = 'groupId';

        $rounds = 200000;
        $enabledCount = 0;

        for ($i = 0; $i < $rounds; $i++) {
            $params = ['percentage' => $percentage, 'groupId' => $groupId];
            $context = new \Unleash\Context();
            $context->sessionId = $i;
            if ($strategy->isEnabled($params, $context)) {
                $enabledCount++;
            }
        }

        $actualPercentage = round($enabledCount / $rounds * 100);
        $highMark = $percentage + 1;
        $lowMark = $percentage - 1;

        $this->assertTrue($lowMark <= $actualPercentage);
        $this->assertTrue($highMark >= $actualPercentage);
    }
}