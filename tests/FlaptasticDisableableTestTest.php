<?php
use \PHPUnit\Framework\TestCase;

use FlaptasticApps\PHPUnit\Listener\FlaptasticDisableableTest;

class FlaptasticDisableableTestTest extends TestCase
{
    public function testCanMockTrait()
    {
        $this->getMockForTrait(FlaptasticDisableableTest::class);
        $this->assertTrue(true);
    }

    public function testCanConstructGuzzleClient()
    {
        new \GuzzleHttp\Client();
        $this->assertTrue(true);
    }
}
