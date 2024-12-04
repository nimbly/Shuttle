<?php

namespace Nimbly\Shuttle\Tests;

use Nimbly\Capsule\Request;
use Nimbly\Capsule\Response;
use Nimbly\Capsule\Stream\BufferStream;
use PHPUnit\Framework\TestCase;
use Nimbly\Shuttle\Body\BufferBody;
use Nimbly\Shuttle\Handler\HandlerInterface;
use Nimbly\Shuttle\Handler\MockHandler;
use Nimbly\Shuttle\Shuttle;

/**
 * @covers Nimbly\Shuttle\Shuttle
 * @covers Nimbly\Shuttle\Handler\MockHandler
 * @covers Nimbly\Shuttle\Handler\CurlHandler
 * @covers Nimbly\Shuttle\Body\BufferBody
 */
class ShuttleTest extends TestCase
{
	public function test_default_user_agent_prefix(): void
	{
		$this->assertEquals(
			"Shuttle/2.0",
			Shuttle::SHUTTLE_USER_AGENT
		);
	}

	public function test_shuttle_creates_default_handler(): void
	{
		$shuttle = new Shuttle;
		$this->assertTrue($shuttle->getHandler() instanceof HandlerInterface);
	}

	public function test_get_response_received(): void
	{
		$shuttle = new Shuttle(
			handler: new MockHandler([
				new Response(200, new BufferStream("OK"), ["Content-Type" => "text/plain"]),
			])
		);

		$response = $shuttle->get("http://example.com");

		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals("OK", $response->getBody()->getContents());
		$this->assertTrue($response->hasHeader("Content-Type"));
		$this->assertEquals("text/plain", $response->getHeaderLine("Content-Type"));
	}

	public function test_post_response_received(): void
	{
		$shuttle = new Shuttle(
			handler: new MockHandler([
				new Response(201, new BufferStream("OK"), ["Content-Type" => "text/plain"]),
			])
		);

		$response = $shuttle->post("http://example.com", new BufferBody("foo"));

		$this->assertEquals(201, $response->getStatusCode());
		$this->assertEquals("OK", $response->getBody()->getContents());
		$this->assertTrue($response->hasHeader("Content-Type"));
		$this->assertEquals("text/plain", $response->getHeaderLine("Content-Type"));
	}

	public function test_patch_response_received(): void
	{
		$shuttle = new Shuttle(
			handler: new MockHandler([
				new Response(200, new BufferStream("OK"), ["Content-Type" => "text/plain"]),
			])
		);

		$response = $shuttle->patch("http://example.com", new BufferBody("foo"));

		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals("OK", $response->getBody()->getContents());
		$this->assertTrue($response->hasHeader("Content-Type"));
		$this->assertEquals("text/plain", $response->getHeaderLine("Content-Type"));
	}

	public function test_put_response_received(): void
	{
		$shuttle = new Shuttle(
			handler: new MockHandler([
				new Response(200, new BufferStream("OK"), ["Content-Type" => "text/plain"]),
			])
		);

		$response = $shuttle->put("http://example.com", new BufferBody("foo"));

		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals("OK", $response->getBody()->getContents());
		$this->assertTrue($response->hasHeader("Content-Type"));
		$this->assertEquals("text/plain", $response->getHeaderLine("Content-Type"));
	}

	public function test_delete_response_received(): void
	{
		$shuttle = new Shuttle(
			handler: new MockHandler([
				new Response(204, new BufferStream(), ["Content-Type" => "text/plain"]),
			])
		);

		$response = $shuttle->delete("http://example.com");

		$this->assertEquals(204, $response->getStatusCode());
		$this->assertEquals("", $response->getBody()->getContents());
	}

	public function test_head_response_received(): void
	{
		$shuttle = new Shuttle(
			handler: new MockHandler([
				new Response(200, new BufferStream("")),
			])
		);

		$response = $shuttle->head("http://example.com");

		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals("", $response->getBody()->getContents());
	}

	public function test_options_response_received(): void
	{
		$shuttle = new Shuttle(
			handler: new MockHandler([
				new Response(200, new BufferStream("")),
			])
		);

		$response = $shuttle->options("http://example.com");

		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals("", $response->getBody()->getContents());
	}

	public function test_send_request_with_default_headers(): void
	{
		$shuttle = new Shuttle(
			handler: new MockHandler([
				function(Request $request): Response {
					return new Response(200, new BufferStream("Ok"), ["X-Default-Header" => $request->getHeaderLine("X-Default-Header")]);
				},
			]),

			headers: [
				"X-Default-Header" => "Shuttle!",
			]
		);

		$response = $shuttle->get("http://example.com");

		$this->assertTrue($response->hasHeader("X-Default-Header"));
		$this->assertEquals("Shuttle!", $response->getHeaderLine("X-Default-Header"));
	}

	public function test_send_request_with_added_headers(): void
	{
		$shuttle = new Shuttle(
			handler: new MockHandler([
				function(Request $request): Response {
					return new Response(
						200,
						new BufferStream("Ok"),
						["X-Added-Header" => $request->getHeaderLine("X-Added-Header")]
					);
				},
			])
		);

		$response = $shuttle->get("http://example.com", ["X-Added-Header" => "Shuttle!"]);

		$this->assertTrue($response->hasHeader("X-Added-Header"));
		$this->assertEquals("Shuttle!", $response->getHeaderLine("X-Added-Header"));
	}
}