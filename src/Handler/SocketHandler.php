<?php

namespace Nimbly\Shuttle\Handler;

use Nimbly\Shuttle\RawResponseTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Nimbly\Shuttle\RequestException;

class SocketHandler implements HandlerInterface
{
	use RawResponseTrait;

	/**
	 * @param integer $timeout Connection timeout, in seconds.
	 */
	public function __construct(
		protected int $timeout = 10
	)
	{
	}

	/**
	 * @inheritDoc
	 */
	public function execute(RequestInterface $request, ResponseInterface $response): ResponseInterface
	{
		$socket = \fsockopen(
			$request->getUri()->getHost(),
			$request->getUri()->getPort() ?: 80,
			$error_number,
			$error_message,
			$this->timeout
		);

		if( $socket === false ){
			throw new RequestException($request, $error_message, $error_number);
		}

		$http_request = $this->buildHttpRequest($request);

		if( \fwrite($socket, $http_request) === false ){
			throw new RequestException($request, "Failed to write headers");
		}

		$raw_response = \stream_get_contents($socket);

		if( \fclose($socket) === false ){
			throw new RequestException($request, "Failed to close socket on completion.");
		}

		return $this->parseRawResponse($raw_response, $response);
	}

	private function buildHttpRequest(RequestInterface $request): string
	{
		$http_request = \sprintf(
			"%s %s %s\r\n",
			\strtoupper($request->getMethod()),
			$request->getRequestTarget(),
			"HTTP/" . $request->getProtocolVersion()
		);

		if( $request->getBody()->getSize() &&
			$request->hasHeader("Content-Length") === false ){
			$request = $request->withHeader("Content-Length", (string) $request->getBody()->getSize());
		}

		foreach( $request->getHeaders() as $header => $value ){
			$http_request .= \sprintf(
				"%s: %s\r\n",
				$header,
				\implode(",", $value)
			);
		}

		if( $request->getBody()->getSize() ){
			$http_request .= \sprintf(
				"\r\n%s\r\n",
				$request->getBody()->getContents()
			);
		}

		return $http_request;
	}
}