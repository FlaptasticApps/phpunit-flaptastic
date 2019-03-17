<?php
use \PHPUnit\Framework\TestCase;

use FlaptasticApps\PHPUnit\Listener\FlaptasticListener;

class FlaptasticListenerTest extends TestCase
{
    public function testCanConstructFlaptasticListener()
    {
        new FlaptasticListener();
        $this->assertTrue(true);
    }
}
