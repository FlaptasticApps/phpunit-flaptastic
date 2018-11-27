<?php
namespace Flaptastic\PHPUnit\Listener;

/**
 * Integrates with flaptastic.com to expose flappy test information.
 */
class FlappyTestsListener implements \PHPUnit_Framework_TestListener
{
    public function startTest(PHPUnit_Framework_Test $test)
    {
        printf("Test '%s' started.\n", $test->getName());
    }
}
