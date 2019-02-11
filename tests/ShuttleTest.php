<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Shuttle\Handler\MockHandler;
use Shuttle\Response;
use Shuttle\Shuttle;
use Shuttle\Stream\BufferStream;
use Shuttle\Handler\HandlerAbstract;

class ShuttleTest extends TestCase
{
    public function test_shuttle_creates_default_handler()
    {
        $shuttle = new Shuttle;
        $this->assertTrue($shuttle->getHandler() instanceof HandlerAbstract);
    }

    public function test_passing_non_handler_as_option_throws_exception()
    {
        $this->expectException(\Exception::class);

        $shuttle = new Shuttle([
            'handler' => 'NotAHandler',
        ]);
    }

    public function test_get_response_received()
    {
        $shuttle = new Shuttle([
            'handler' => new MockHandler([
                new Response(200, new BufferStream("OK"), ["Content-Type" => "text/plain"]),
            ])
        ]);

        $response = $shuttle->get("http://example.com");

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("OK", $response->getBody()->getContents());
        $this->assertTrue($response->hasHeader("Content-Type"));
        $this->assertEquals("Content-Type: text/plain", $response->getHeaderLine("Content-Type"));
    }
}