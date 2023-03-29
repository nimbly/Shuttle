<?php

namespace Nimbly\Shuttle;

use Exception;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Throwable;

class RequestException extends Exception implements RequestExceptionInterface
{
	/**
	 * @param RequestInterface $request
	 * @param string $message
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	public function __construct(
		protected RequestInterface $request,
		string $message = "",
		int $code = 0,
		?Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}

	/**
	 * Get the request instance.
	 *
	 * @return RequestInterface
	 */
	public function getRequest(): RequestInterface
	{
		return $this->request;
	}
}