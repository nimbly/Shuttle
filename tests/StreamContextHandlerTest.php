<?php

namespace Shuttle\Tests;

use PHPUnit\Framework\TestCase;
use Shuttle\Handler\StreamContextHandler;
use Capsule\Request;
use Capsule\Response;
use Capsule\Stream\BufferStream;

/**
 * @covers Shuttle\Handler\StreamContextHandler
 */
class StreamContextHandlerTest extends TestCase
{
    public function test_default_options()
    {
        $streamHandler = new StreamContextHandler;
        $reflection = new \ReflectionClass($streamHandler);

        $property = $reflection->getProperty("options");
        $property->setAccessible(true);

        $options = $property->getValue($streamHandler);

        $this->assertEquals(1, $options['follow_location']);
        $this->assertEquals(10, $options['max_redirects']);
        $this->assertEquals(120, $options['timeout']);
        $this->assertEquals(true, $options['ignore_errors']);
        $this->assertEquals(false, $options['request_fulluri']);
    }

    public function test_set_debug()
    {
        $streamHandler = new StreamContextHandler;
        $reflection = new \ReflectionClass($streamHandler);

        $streamHandler->setDebug(true);
        $debug = $reflection->getProperty("debug");
        $debug->setAccessible(true);

        $this->assertTrue($debug->getValue($streamHandler));
    }

    public function test_execute_request()
    {
        $streamHandler = new StreamContextHandler;

        $response = $streamHandler->execute(
            new Request("get", "https://github.com")
        );

        $this->assertTrue(($response instanceof Response));
    }
}