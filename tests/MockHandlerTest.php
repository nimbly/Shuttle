<?php

namespace Nimbly\Shuttle\Tests;

use Nimbly\Capsule\Request;
use Nimbly\Capsule\Response;
use Nimbly\Capsule\Stream\BufferStream;
use PHPUnit\Framework\TestCase;
use Nimbly\Shuttle\Handler\MockHandler;

/**
 * @covers Nimbly\Shuttle\Handler\MockHandler
 */
class MockHandlerTest extends TestCase
{
	public function test_response_instances_in_queue()
	{
		$mockHandler = new MockHandler([
			new Response(200, new BufferStream("Ok"))
		]);

		$response = $mockHandler->execute(
			new Request("get", "http://example.com"),
			new Response(200)
		);

		$this->assertTrue($response instanceof Response);
	}

	public function test_closures_in_queue()
	{
		$mockHandler = new MockHandler([
			function(Request $request): Response {
				return new Response(200, new BufferStream("Ok"));
			}
		]);

		$response = $mockHandler->execute(
			new Request("get", "http://example.com"),
			new Response(200)
		);

		$this->assertTrue($response instanceof Response);
	}

	public function test_no_responses_in_queue_throws_exception()
	{
		$this->expectException(\Exception::class);
		$mockHandler = new MockHandler([]);
		$mockHandler->execute(
			new Request("get", "http://example.com"),
			new Response(200)
		);
	}
}