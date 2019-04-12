<?php

namespace Shuttle\Tests;

use Capsule\Request;
use Capsule\Response;
use Capsule\Stream\BufferStream;
use PHPUnit\Framework\TestCase;
use Shuttle\Handler\MockHandler;

/**
 * @covers Shuttle\Handler\MockHandler
 */
class MockHandlerTest extends TestCase
{
    public function test_response_instances_in_queue()
    {
        $mockHandler = new MockHandler([
            new Response(200, new BufferStream("Ok"))
        ]);

        $response = $mockHandler->execute(new Request("get", "http://example.com"));

        $this->assertTrue($response instanceof Response);
    }

    public function test_closures_in_queue()
    {
        $mockHandler = new MockHandler([
            function(Request $request): Response {
                return new Response(200, new BufferStream("Ok"));
            }
        ]);

        $response = $mockHandler->execute(new Request("get", "http://example.com"));

        $this->assertTrue($response instanceof Response);
    }

    public function test_no_responses_in_queue_throws_exception()
    {
        $this->expectException(\Exception::class);
        $mockHandler = new MockHandler([]);
        $mockHandler->execute(new Request("get", "http://example.com"));
    }

    public function test_setting_debug_mode()
    {
        $mockHandler = new MockHandler([]);
        $mockHandler->setDebug(true);
        
        $reflection = new \ReflectionClass($mockHandler);
        $debug = $reflection->getProperty('debug');
        $debug->setAccessible(true);

        $this->assertTrue($debug->getValue($mockHandler));        
    }
}