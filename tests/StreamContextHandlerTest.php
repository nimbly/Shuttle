<?php

namespace Nimbly\Shuttle\Tests;

use Nimbly\Capsule\Request;
use Nimbly\Capsule\Response;
use Nimbly\Shuttle\Handler\StreamContextHandler;
use Nimbly\Shuttle\RequestException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @covers Nimbly\Shuttle\Handler\StreamContextHandler
 * @covers Nimbly\Shuttle\RequestException
 */
class StreamContextHandlerTest extends TestCase
{
	public function test_default_options(): void
	{
		$streamContextHandler = new StreamContextHandler;

		$reflectionClass = new ReflectionClass($streamContextHandler);
		$reflectionProperty = $reflectionClass->getProperty("options");
		$reflectionProperty->setAccessible(true);

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
		$reflectionMethod->setAccessible(true);

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