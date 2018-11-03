<?php

namespace Test\Utils;

use PHPUnit\Framework\TestCase;

class NormalizerTest extends TestCase
{
    public function testNormalizer(){
        $this->assertEquals(73, normalizeValue('123','gr1'));
        $this->assertEquals(25, normalizeValue('999','groupX'));
    }
}