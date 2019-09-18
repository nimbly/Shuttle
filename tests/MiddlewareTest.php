<?php

namespace Shuttle\Tests;

use Capsule\Request;
use Capsule\Response;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Shuttle\Handler\MockHandler;
use Shuttle\Shuttle;
use Shuttle\Tests\src\RequestMiddleware;

/**
 * @covers Shuttle\Shuttle
 * @covers Shuttle\Handler\MockHandler
 */
class MiddlewareTest extends TestCase
{
    public function test_middleware_compiles()
    {
        $shuttle = new Shuttle([
            'handler' => new MockHandler([
                new Response(200, "OK"),
            ]),

            'middleware' => [
                new RequestMiddleware
            ]
        ]);

        $reflection = new ReflectionClass($shuttle);
        $property = $reflection->getProperty('options');
        $property->setAccessible(true);
        $options = $property->getValue($shuttle);

        $method = $reflection->getMethod('compileMiddleware');
        $method->setAccessible(true);

        $middleware = $method->invokeArgs($shuttle, [
            $options['middleware'],
            [$shuttle->getHandler(), 'execute']
        ]);

        $response = \call_user_func($middleware, new Request("post", "/path"));

        $this->assertEquals("Foo", $response->getHeader("X-Middleware")[0]);
    }
}