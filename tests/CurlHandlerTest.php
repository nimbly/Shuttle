<?php

namespace Shuttle\Tests;

use PHPUnit\Framework\TestCase;
use Shuttle\Handler\CurlHandler;
use Capsule\Request;
use Capsule\Response;
use Capsule\Stream\BufferStream;

/**
 * @covers Shuttle\Handler\CurlHandler
 */
class CurlHandlerTest extends TestCase
{
    public function test_default_options()
    {
        $curlHandler = new CurlHandler;
        $reflection = new \ReflectionClass($curlHandler);

        $property = $reflection->getProperty("options");
        $property->setAccessible(true);

        $options = $property->getValue($curlHandler);

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

    public function test_set_debug()
    {
        $curlHandler = new CurlHandler;
        $reflection = new \ReflectionClass($curlHandler);

        $curlHandler->setDebug(true);

        $property = $reflection->getProperty("options");
        $property->setAccessible(true);

        $options = $property->getValue($curlHandler);

        $this->assertEquals(
            true,
            $options[CURLOPT_VERBOSE]
        );
    }

    public function test_default_response_stream()
    {
        $curlHandler = new CurlHandler;
        $reflection = new \ReflectionClass($curlHandler);

        $method = $reflection->getMethod('makeResponseBodyStream');
        $method->setAccessible(true);
        $responseStream = $method->invoke($curlHandler);

        $this->assertEquals(
            "php://temp/maxmemory:2097152",
            $responseStream->getMetadata("uri")
        );
    }

    public function test_setting_max_memory_on_response_stream()
    {
        $curlHandler = new CurlHandler;
        $reflection = new \ReflectionClass($curlHandler);

        $curlHandler->setMaxResponseBodyMemory(1024);

        $method = $reflection->getMethod('makeResponseBodyStream');
        $method->setAccessible(true);
        $responseStream = $method->invoke($curlHandler);

        $this->assertEquals(
            "php://temp/maxmemory:1024",
            $responseStream->getMetadata("uri")
        );
    }

    public function test_http_v1_curl_option()
    {
        $curlHandler = new CurlHandler;
        $reflection = new \ReflectionClass($curlHandler);

        $request = new Request("get", "http://example.com");
        $request = $request->withProtocolVersion("1");

        $method = $reflection->getMethod('buildRequestHttpProtocolVersion');
        $method->setAccessible(true);
        $httpVersion = $method->invoke($curlHandler, $request);

        $this->assertEquals(
            CURL_HTTP_VERSION_1_0,
            $httpVersion
        );
    }

    public function test_http_v1_1_curl_option()
    {
        $curlHandler = new CurlHandler;
        $reflection = new \ReflectionClass($curlHandler);

        $request = new Request("get", "http://example.com");
        $request = $request->withProtocolVersion("1.1");

        $method = $reflection->getMethod('buildRequestHttpProtocolVersion');
        $method->setAccessible(true);
        $httpVersion = $method->invoke($curlHandler, $request);

        $this->assertEquals(
            CURL_HTTP_VERSION_1_1,
            $httpVersion
        );
    }

    public function test_http_v2_curl_option()
    {
        $curlHandler = new CurlHandler;
        $reflection = new \ReflectionClass($curlHandler);

        $request = new Request("get", "http://example.com");
        $request = $request->withProtocolVersion("2");

        $method = $reflection->getMethod('buildRequestHttpProtocolVersion');
        $method->setAccessible(true);
        $httpVersion = $method->invoke($curlHandler, $request);

        $this->assertEquals(
            CURL_HTTP_VERSION_2,
            $httpVersion
        );
    }

    public function test_curl_headers()
    {
        $curlHandler = new CurlHandler;
        $reflection = new \ReflectionClass($curlHandler);

        $request = new Request("get", "http://example.com");
        $request = $request
        ->withHeader("X-Foo", "Bar")
        ->withHeader("X-Bar", "Foo");

        $method = $reflection->getMethod('buildRequestHeaders');
        $method->setAccessible(true);
        $headers = $method->invoke($curlHandler, $request);

        $this->assertTrue(\is_array($headers));

        $this->assertEquals([
			"Host: example.com",
			"X-Foo: Bar",
			"X-Bar: Foo",
		], $headers);
    }

    public function test_curl_request_options()
    {
        $curlHandler = new CurlHandler;
        $reflection = new \ReflectionClass($curlHandler);

        $request = new Request("post", "http://example.com:8000", new BufferStream("OK"));
        $response = new Response(200);

        $method = $reflection->getMethod('buildCurlRequestOptions');
        $method->setAccessible(true);
        $requestOptions = $method->invokeArgs($curlHandler, [$request, &$response]);

        $this->assertEquals(CURL_HTTP_VERSION_1_1, $requestOptions[CURLOPT_HTTP_VERSION]);
        $this->assertEquals("POST", $requestOptions[CURLOPT_CUSTOMREQUEST]);
        $this->assertEquals(8000, $requestOptions[CURLOPT_PORT]);
        $this->assertEquals("http://example.com:8000", $requestOptions[CURLOPT_URL]);
        $this->assertTrue(is_callable($requestOptions[CURLOPT_WRITEFUNCTION]));
        $this->assertTrue(is_callable($requestOptions[CURLOPT_HEADERFUNCTION]));
        $this->assertEquals("OK", $requestOptions[CURLOPT_POSTFIELDS]);
    }

    public function test_execute_request()
    {
        $curlHandler = new CurlHandler;

        $response = $curlHandler->execute(
            new Request("get", "https://github.com")
        );

        $this->assertTrue(($response instanceof Response));
    }
}