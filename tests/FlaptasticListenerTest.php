<?php
use \PHPUnit\Framework\TestCase;

use BlockJon\PHPUnit\Listener\FlaptasticListener;

class FlaptasticListenerTest extends TestCase
{
    public function testCanConstructFlaptasticListener()
    {
        new FlaptasticListener();
        $this->assertTrue(true);
    }
}
