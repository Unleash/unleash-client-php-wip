<?php

namespace Tests\Strategy;

use Unleash\Strategy\GradualRolloutRandomStrategy;

class GradualRolloutRandomStrategyTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldOnlyAtMostMissByOnePercent()
    {
        $strategy = new GradualRolloutRandomStrategy();

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