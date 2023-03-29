<?php

namespace Nimbly\Shuttle\Tests;

use Nimbly\Capsule\Request;
use Nimbly\Capsule\Response;
use Nimbly\Capsule\Stream\BufferStream;
use Nimbly\Shuttle\Handler\CurlHandler;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @covers Nimbly\Shuttle\Handler\CurlHandler
 */
class CurlHandlerTest extends TestCase
{
	public function test_default_options(): void
	{
		$curlHandler = new CurlHandler;
		$reflectionClass = new ReflectionClass($curlHandler);

		$reflectionProperty = $reflectionClass->getProperty("options");
		$reflectionProperty->setAccessible(true);

		$options = $reflectionProperty->getValue($curlHandler);

		$this->assertEquals(
			true,
			$options[CURLOPT_FOLLOWLOCATION]
		);

		$this->assertEquals(
			10,
			$options[CURLOPT_MAXREDIRS]
		);

		$this->assertEquals(
			false,
			$options[CURLOPT_HEADER]
		);

		$this->assertEquals(
			false,
			$options[CURLOPT_RETURNTRANSFER]
		);

		$this->assertEquals(
			120,
			$options[CURLOPT_CONNECTTIMEOUT]
		);

		$this->assertEquals(
			true,
			$options[CURLOPT_SSL_VERIFYPEER]
		);

		$this->assertEquals(
			(CURLPROTO_HTTP | CURLPROTO_HTTPS),
			$options[CURLOPT_PROTOCOLS]
		);

		$this->assertEquals(
			false,
			$options[CURLOPT_VERBOSE]
		);
	}

	public function test_http_v1_curl_option(): void
	{
		$curlHandler = new CurlHandler;
		$reflectionClass = new ReflectionClass($curlHandler);

		$request = new Request("get", "http://example.com");
		$request = $request->withProtocolVersion("1");

		$reflectionMethod = $reflectionClass->getMethod("buildRequestHttpProtocolVersion");
		$reflectionMethod->setAccessible(true);
		$httpVersion = $reflectionMethod->invoke($curlHandler, $request);

		$this->assertEquals(
			CURL_HTTP_VERSION_1_0,
			$httpVersion
		);
	}

	public function test_http_v1_1_curl_option(): void
	{
		$curlHandler = new CurlHandler;
		$reflectionClass = new ReflectionClass($curlHandler);

		$request = new Request("get", "http://example.com");
		$request = $request->withProtocolVersion("1.1");

		$reflectionMethod = $reflectionClass->getMethod("buildRequestHttpProtocolVersion");
		$reflectionMethod->setAccessible(true);
		$httpVersion = $reflectionMethod->invoke($curlHandler, $request);

		$this->assertEquals(
			CURL_HTTP_VERSION_1_1,
			$httpVersion
		);
	}

	public function test_http_v2_curl_option(): void
	{
		$curlHandler = new CurlHandler;
		$reflectionClass = new ReflectionClass($curlHandler);

		$request = new Request("get", "http://example.com");
		$request = $request->withProtocolVersion("2");

		$reflectionMethod = $reflectionClass->getMethod("buildRequestHttpProtocolVersion");
		$reflectionMethod->setAccessible(true);
		$httpVersion = $reflectionMethod->invoke($curlHandler, $request);

		$this->assertEquals(
			CURL_HTTP_VERSION_2,
			$httpVersion
		);
	}

	public function test_curl_headers(): void
	{
		$curlHandler = new CurlHandler;
		$reflectionClass = new ReflectionClass($curlHandler);

		$request = new Request("get", "http://example.com");
		$request = $request
		->withHeader("X-Foo", "Bar")
		->withHeader("X-Bar", "Foo");

		$reflectionMethod = $reflectionClass->getMethod("buildRequestHeaders");
		$reflectionMethod->setAccessible(true);
		$headers = $reflectionMethod->invoke($curlHandler, $request);

		$this->assertTrue(\is_array($headers));

		$this->assertEquals([
			"Host:example.com",
			"X-Foo:Bar",
			"X-Bar:Foo",
		], $headers);
	}

	public function test_curl_request_options(): void
	{
		$curlHandler = new CurlHandler;
		$reflectionClass = new ReflectionClass($curlHandler);

		$request = new Request("post", "http://example.com:8000", new BufferStream("OK"));
		$response = new Response(200);

		$reflectionMethod = $reflectionClass->getMethod("buildCurlRequestOptions");
		$reflectionMethod->setAccessible(true);
		$requestOptions = $reflectionMethod->invokeArgs($curlHandler, [$request, &$response]);

		$this->assertEquals(CURL_HTTP_VERSION_1_1, $requestOptions[CURLOPT_HTTP_VERSION]);
		$this->assertEquals("POST", $requestOptions[CURLOPT_CUSTOMREQUEST]);
		$this->assertEquals(8000, $requestOptions[CURLOPT_PORT]);
		$this->assertEquals("http://example.com:8000/", $requestOptions[CURLOPT_URL]);
		$this->assertTrue(is_callable($requestOptions[CURLOPT_WRITEFUNCTION]));
		$this->assertTrue(is_callable($requestOptions[CURLOPT_HEADERFUNCTION]));
		$this->assertEquals("OK", $requestOptions[CURLOPT_POSTFIELDS]);
	}

	public function test_execute_request(): void
	{
		$curlHandler = new CurlHandler;

		$response = $curlHandler->execute(
			new Request("get", "https://github.com"),
			new Response(200)
		);

		$this->assertTrue(($response instanceof Response));
	}
}