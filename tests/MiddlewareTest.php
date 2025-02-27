<?php

namespace Nimbly\Shuttle\Tests;

use ReflectionClass;
use Nimbly\Capsule\Request;
use Nimbly\Shuttle\Shuttle;
use Nimbly\Capsule\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Nimbly\Shuttle\Handler\MockHandler;
use Nimbly\Shuttle\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;

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
				new class implements MiddlewareInterface
				{
					public function process(RequestInterface $request, callable $next): ResponseInterface
					{
						$response = $next($request);
						return $response->withAddedHeader("X-Middleware", "Foo");
					}
				}
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