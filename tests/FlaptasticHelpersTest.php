<?php
use \PHPUnit\Framework\TestCase;

use FlaptasticApps\PHPUnit\Listener\FlaptasticHelpers;

class FlaptasticHelpersTest extends TestCase
{
    public function testCanConstructFlaptasticHelpers()
    {
        new FlaptasticHelpers();
        $this->assertTrue(true);
    }
}
