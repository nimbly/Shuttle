<?php

namespace Nimbly\Shuttle\Tests;

use PHPUnit\Framework\TestCase;
use Nimbly\Capsule\Request;
use Nimbly\Shuttle\RequestException;
use Psr\Http\Message\RequestInterface;

/**
 * @covers Nimbly\Shuttle\RequestException
 */
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