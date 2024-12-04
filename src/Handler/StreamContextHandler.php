<?php

namespace Nimbly\Shuttle\Handler;

use Nimbly\Shuttle\HandlerException;
use Nimbly\Shuttle\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * A PHP Stream Context handler. This handler requires remote URL access to `fopen`, so make sure that `allow_url_fopen` configuration is enabled. Typically you would only use this handler if you cannot use the `CurlHandler`.
 */
class StreamContextHandler implements HandlerInterface
{
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
	 * @var array<string,mixed>
	 */
	protected array $ssl_options = [];

	/**
	 * @param array<string,mixed> $options Array of HTTP stream context key => value pairs. See http://php.net/manual/en/context.http.php for list of available options.
	 * @param array<string,mixed> $ssl_options Array of stream SSL options as key => value pairs. See https://www.php.net/manual/en/context.ssl.php for list of available options.
	 */
	public function __construct(
		array $options = [],
		array $ssl_options = [])
	{
		/**
		 * @psalm-suppress InvalidPropertyAssignmentValue
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
				$error["type"] ?? -1
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
		return \array_map(
			function(string $name, array $values): string {
				return $name . ": " . \implode(",", $values);
			},
			\array_keys($request_headers),
			$request_headers
		);
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

	/**
	 * Process and parse a raw string HTTP response.
	 *
	 * @param string $raw_response
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 */
	private function parseRawResponse(string $raw_response, ResponseInterface $response): ResponseInterface
	{
		$lines = \explode("\n", $raw_response);

		$part = "headers";

		foreach( $lines as $line ){
			if( \trim($line) === "" ){
				$part = "body";
				continue;
			}

			if( $part === "headers" ){
				if( \preg_match("/^HTTP\/([\d\.]+) ([\d]{3})(?: ([\w\h]+))?\R?+$/i", \trim($line), $httpResponse) ){
					$response = $response->withStatus((int) $httpResponse[2], $httpResponse[3] ?? "");
					$response = $response->withProtocolVersion($httpResponse[1]);
				}

				elseif( \preg_match("/^([\w\d\-_]+)\: (\N+)\R?+$/", \trim($line), $httpHeader) ){
					$response = $response->withAddedHeader($httpHeader[1], $httpHeader[2]);
				}
			}
			else {
				$response->getBody()->write($line);
			}
		}

		if( $response->getBody()->isSeekable() ){
			$response->getBody()->rewind();
		}

		return $response;
	}
}