<?php

namespace Test;

use PHPUnit\Framework\TestCase;

class Unleash extends TestCase
{
    public function testShouldHandleOldUrl()
    {
        $instance = new \Unleash\Unleash(
            'foo',
            'http://test.nl/client/features',
            null,
            0,
            0,
            true
        );

        $instance->addListener('warn', function ($warn){
            $this->assertNotNull($warn);
        });

        $instance->destroy();
    }
}