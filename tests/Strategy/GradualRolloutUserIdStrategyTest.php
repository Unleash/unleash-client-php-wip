<?php

namespace Test\Strategy;

use PHPUnit\Framework\TestCase;
use Unleash\Strategy\GradualRolloutUserIdStrategy;

class GradualRolloutUserIdStrategyTest extends TestCase
{
    public function testCorrectName()
    {
        $strategy = new GradualRolloutUserIdStrategy();
        $this->assertEquals('gradualRolloutUserId', $strategy->name);
    }

    public function testAlways100Percentage()
    {
        $strategy = new GradualRolloutUserIdStrategy();
        $context = new \Unleash\Context();
        $context->userId = '123';
        $parameters = ['percentage' => 100, 'groupId' => 'gr1'];
        $this->assertTrue($strategy->isEnabled($parameters, $context));
    }

    public function testAlways0Percentage()
    {
        $strategy = new GradualRolloutUserIdStrategy();
        $context = new \Unleash\Context();
        $context->userId = '123';
        $parameters = ['percentage' => 0, 'groupId' => 'gr1'];
        $this->assertFalse($strategy->isEnabled($parameters, $context));
    }

    public function testShouldBeEnabledWhenPercentageIsExactlySame()
    {
        $strategy = new GradualRolloutUserIdStrategy();
        $context = new \Unleash\Context();

        $userId = '123123';
        $groupId = 'group1';

        $percentage = normalizeValue($userId, $groupId);
        $context->userId = $userId;
        $parameters = ['percentage' => $percentage, 'groupId' => $groupId];
        $this->assertTrue($strategy->isEnabled($parameters, $context));
    }

    public function testShouldBeDisabledWhenPercentageIsJustBelowRequiredValue()
    {
        $strategy = new GradualRolloutUserIdStrategy();
        $context = new \Unleash\Context();

        $userId = '123123';
        $groupId = 'group1';

        $percentage = normalizeValue($userId, $groupId) - 1;
        $parameters = ['percentage' => $percentage, 'groupId' => $groupId];
        $context->userId = $userId;
        $this->assertFalse($strategy->isEnabled($parameters, $context));
    }

    public function testShouldOlyAtMostMissByOnePercent()
    {
        $strategy = new GradualRolloutUserIdStrategy();

        $percentage = 25;
        $groupId = 'groupId';

        $rounds = 200000;
        $enabledCount = 0;

        for ($i = 0; $i < $rounds; $i++) {
            $params = ['percentage' => $percentage, 'groupId' => $groupId];
            $context = new \Unleash\Context();
            $context->userId = $i;
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