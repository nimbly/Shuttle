<?php

namespace Shuttle\Tests;

use PHPUnit\Framework\TestCase;
use Shuttle\Request;
use Shuttle\RequestException;
use Psr\Http\Message\RequestInterface;

class RequestExceptionTest extends TestCase
{
    public function test_request_exception_returns_request_instance()
    {
        $requestException = new RequestException(
            new Request("get", "https://www.google.com"),
            "Bad Request",
            400
        );

        $this->assertTrue($requestException->getRequest() instanceof RequestInterface);
    }
}