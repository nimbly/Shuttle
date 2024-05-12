<?php

namespace Nimbly\Shuttle\Handler;

use Nimbly\Shuttle\HandlerException;
use Nimbly\Shuttle\RawResponseTrait;
use Nimbly\Shuttle\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class StreamContextHandler implements HandlerInterface
{
	use RawResponseTrait;

	/**
	 * Default stream handler HTTP options.
	 *
	 * @var array{follow_location:int,ignore_errors:bool,request_fulluri:bool,max_redirects:int,timeout:int}
	 */
	protected array $options = [
		"follow_location" => 0,
		"ignore_errors" => true,
		"request_fulluri" => false,
		"max_redirects" => 10,
		"timeout" => 120,
	];

	/**
	 * Default stream handler SSL options.
	 *
	 * @var array
	 */
	protected array $ssl_options = [];

	/**
	 * @param array<string,mixed> $options Array of HTTP stream context key => value pairs. See http://php.net/manual/en/context.http.php for list of available options.
	 */
	public function __construct(
		array $options = [],
		array $ssl_options = [])
	{
		/**
		 * @psalm-suppress PropertyTypeCoercion
		 */
		$this->options = \array_merge($this->options, $options);
		$this->ssl_options = \array_merge($this->ssl_options, $ssl_options);
	}

	/**
	 * @inheritDoc
	 */
	public function execute(RequestInterface $request, ResponseInterface $response): ResponseInterface
	{
		$context = \stream_context_create(
			[
				"http" => \array_merge(
					[
						"protocol_version" => $request->getProtocolVersion(),
						"method" => $request->getMethod(),
						"user_agent" => $request->getHeaderLine("User-Agent"),
						"header" => $this->buildRequestHeaders($request->getHeaders()),
						"content" => $request->getBody()->getContents(),
					],
					$this->options
				),
				"ssl" => \array_merge(
					$this->ssl_options,
					[
						"peer_name" => $request->getUri()->getHost()
					]
				)
			]
		);

		$stream = \fopen((string) $request->getUri(), "r", false, $context);

		if( $stream === false ){
			$error = \error_get_last();
			throw new RequestException(
				$request,
				$error["message"] ?? "Failed to open stream",
				$error["code"] ?? -1
			);
		}

		$response = $this->parseResponse($stream, $response);

		if( \fclose($stream) === false ){
			throw new RequestException(
				$request,
				"Failed to close stream context."
			);
		}

		return $response;
	}


	/**
	 * Build the request headers.
	 *
	 * @param array<string,array<string>> $request_headers
	 * @return array<string>
	 */
	private function buildRequestHeaders(array $request_headers): array
	{
		$headers = [];

		foreach( $request_headers as $key => $values ){
			foreach( $values as $value ){
				$headers[] = "{$key}: {$value}";
			}
		}

		return $headers;
	}

	/**
	 * Create the Response object from the Stream.
	 *
	 * @param resource $stream
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 */
	private function parseResponse($stream, ResponseInterface $response): ResponseInterface
	{
		$raw_response = \sprintf(
			"%s\r\n",
			\implode("\r\n", \stream_get_meta_data($stream)["wrapper_data"] ?? [])
		);

		$body = \stream_get_contents($stream);

		if( $body === false ){
			throw new HandlerException("Failed to read response body from stream.");
		}
		elseif( $body ){
			$raw_response .= ("\r\n" . $body);
		}

		return $this->parseRawResponse($raw_response, $response);
	}
}