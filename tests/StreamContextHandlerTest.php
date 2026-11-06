<?php

namespace Nimbly\Shuttle\Tests;

use Nimbly\Capsule\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use Nimbly\Capsule\Response;
use Nimbly\Shuttle\Handler\StreamContextHandler;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(StreamContextHandler::class)]
class StreamContextHandlerTest extends TestCase
{
	public function test_default_options(): void
	{
		$streamContextHandler = new StreamContextHandler;

		$reflectionClass = new ReflectionClass($streamContextHandler);
		$reflectionProperty = $reflectionClass->getProperty("options");

		$options = $reflectionProperty->getValue($streamContextHandler);

		$this->assertEquals(0, $options["follow_location"]);
		$this->assertEquals(10, $options["max_redirects"]);
		$this->assertEquals(120, $options["timeout"]);
		$this->assertEquals(true, $options["ignore_errors"]);
		$this->assertEquals(false, $options["request_fulluri"]);
	}

	public function test_build_request_headers(): void
	{
		$streamContextHandler = new StreamContextHandler;
		$reflectionClass = new ReflectionClass($streamContextHandler);

		$reflectionMethod = $reflectionClass->getMethod("buildRequestHeaders");

		$headers = $reflectionMethod->invokeArgs($streamContextHandler, [
			["X-Header-1" => ["Foo"], "X-Header-2" => ["Bar"]]
		]);

		$this->assertEquals([
			"X-Header-1: Foo",
			"X-Header-2: Bar"
		], $headers);
	}

	public function test_execute_request(): void
	{
		$streamContextHandler = new StreamContextHandler;

		$response = $streamContextHandler->execute(
			new Request("get", "https://github.com"),
			new Response(200)
		);

		$this->assertTrue(($response instanceof Response));
	}
}