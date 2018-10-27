<?php

namespace Test;

use PHPUnit\Framework\TestCase;
use Unleash\Events\WarnEvent;

class Unleash extends TestCase
{
    public function testShouldHandleOldUrl()
    {
        $instance = new \Unleash\Unleash();
        $instance->addListener('warn', function (WarnEvent $event) {
            $this->assertNotNull($event);
        });

        $instance->init('foo',
            'http://test.nl/client/features',
            null,
            0,
            0,
            true
        );


        $instance->destroy();
    }
}