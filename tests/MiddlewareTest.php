<?php

namespace Nimbly\Shuttle\Tests;

use Nimbly\Capsule\Request;
use Nimbly\Capsule\Response;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Nimbly\Shuttle\Handler\MockHandler;
use Nimbly\Shuttle\Shuttle;
use Nimbly\Shuttle\Tests\src\RequestMiddleware;

/**
 * @covers Nimbly\Shuttle\Shuttle
 * @covers Nimbly\Shuttle\Handler\MockHandler
 */
class MiddlewareTest extends TestCase
{
	public function test_middleware_compiles()
	{
		$shuttle = new Shuttle(
			handler: new MockHandler([new Response(200, "OK")]),
			middleware: [
				new RequestMiddleware
			]
		);

		$reflectionClass = new ReflectionClass($shuttle);
		$reflectionProperty = $reflectionClass->getProperty("middleware");
		$reflectionProperty->setAccessible(true);
		$middleware = $reflectionProperty->getValue($shuttle);

		$response = \call_user_func($middleware, new Request("post", "/path"));

		$this->assertEquals("Foo", $response->getHeaderLine("X-Middleware"));
	}
}