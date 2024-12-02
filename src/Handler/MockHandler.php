<?php

namespace Nimbly\Shuttle\Handler;

use Nimbly\Shuttle\HandlerException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class MockHandler implements HandlerInterface
{
	/**
	 * @param array<ResponseInterface|callable> $responses An array of ResponseInterface instances to be returned with each call. Alternatively, you can pass in a callable that must return a ResponseInterface instance. The RequestInterface instance will be passed into the callable.
	 */
	public function __construct(protected array $responses = [])
	{
	}

	/**
	 * @inheritDoc
	 */
	public function execute(RequestInterface $request, ResponseInterface $response): ResponseInterface
	{
		if( empty($this->responses) ){
			throw new HandlerException("No more responses available in MockHandler response queue.");
		}

		$response = \array_shift($this->responses);

		if( \is_callable($response) ){
			return \call_user_func($response, $request);
		}

		return $response;
	}
}