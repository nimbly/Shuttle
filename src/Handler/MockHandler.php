<?php

namespace Nimbly\Shuttle\Handler;

use Nimbly\Shuttle\HandlerException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class MockHandler implements HandlerInterface
{
	/**
	 * MockHandler constructor.
	 *
	 * Pass in an array of Response instances that will be returned. You may also
	 * pass in a closure that takes a RequestInterface and must return a ResponseInterface.
	 *
	 * @param array<ResponseInterface|callable> $responses
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