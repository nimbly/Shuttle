<?php

namespace Shuttle\Tests;

use Capsule\Request;
use Capsule\Response;
use Capsule\Stream\BufferStream;
use PHPUnit\Framework\TestCase;
use Shuttle\Handler\StreamContextHandler;
use Shuttle\RequestException;

/**
 * @covers Shuttle\Handler\StreamContextHandler
 * @covers Shuttle\RequestException
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

    public function test_build_request_headers()
    {
        $streamHandler = new StreamContextHandler;
        $reflection = new \ReflectionClass($streamHandler);

        $method = $reflection->getMethod('buildRequestHeaders');
        $method->setAccessible(true);

        $headers = $method->invokeArgs($streamHandler, [
            ["X-Header-1" => ["Foo"], "X-Header-2" => ["Bar"]]
        ]);

        $this->assertEquals([
            "X-Header-1: Foo",
            "X-Header-2: Bar"
        ], $headers);
    }

    public function test_build_http_context()
    {
        $streamHandler = new StreamContextHandler;
        $reflection = new \ReflectionClass($streamHandler);

        $method = $reflection->getMethod('buildHttpContext');
        $method->setAccessible(true);

        $request = new Request("post", "http://example.com", "Ok", ["X-Header-1" => "Foo"], "2");

        $httpContext = $method->invoke($streamHandler, $request);

        $this->assertEquals([
            'follow_location' => 1,
            'request_fulluri' => false,
            'max_redirects' => 10,
            'ignore_errors' => true,
            'timeout' => 120,
            'method' => 'POST',
            'protocol_version' => '2',
            'header' => [
				"Host: example.com",
				"X-Header-1: Foo"
			],
            'content' => 'Ok'
        ], $httpContext);
    }

    public function test_build_stream_with_invalid_uri_throws()
    {
        $streamHandler = new StreamContextHandler;
        $reflection = new \ReflectionClass($streamHandler);

        $method = $reflection->getMethod('buildStream');
        $method->setAccessible(true);

        $request = new Request("get", "http://localhost:1");

        $this->expectException(RequestException::class);
        $stream = $method->invokeArgs($streamHandler, [$request, ['http' => ['method' => 'GET', 'protocol_version' => '1.1']]]);
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