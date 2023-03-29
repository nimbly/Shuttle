<?php

namespace Nimbly\Shuttle\Handler;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface HandlerInterface
{
	/**
	 * Execute the given Request.
	 *
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 */
	public function execute(RequestInterface $request, ResponseInterface $response): ResponseInterface;
}