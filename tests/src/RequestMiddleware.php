<?php

namespace Nimbly\Shuttle\Tests\src;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Nimbly\Shuttle\MiddlewareInterface;

class RequestMiddleware implements MiddlewareInterface
{
	public function process(RequestInterface $request, callable $next): ResponseInterface
	{
		$response = $next($request);
		$response = $response->withAddedHeader("X-Middleware", "Foo");

		return $response;
	}
}