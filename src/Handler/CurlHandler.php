<?php

namespace Nimbly\Shuttle\Handler;

use CurlHandle;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Nimbly\Shuttle\HandlerException;
use Nimbly\Shuttle\RequestException;

/**
 * A cURL HTTP handler. This handler requires the PHP `ext-curl` extension. If no handler is specified when instantiating `Shuttle`, this handler will be used by default.
 */
class CurlHandler implements HandlerInterface
{
	private CurlHandle $curlHandle;

	/**
	 * Set of default options.
	 *
	 * @var array<int,mixed>
	 */
	protected array $options = [
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_HEADER => false,
		CURLOPT_RETURNTRANSFER => false,
		CURLOPT_CONNECTTIMEOUT => 120,
		CURLOPT_SSL_VERIFYPEER => true,
		CURLOPT_SSL_VERIFYHOST => 2,
		CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
		CURLOPT_VERBOSE => false
	];


	/**
	 * CurlHandler constructor.
	 *
	 * @param array<int,mixed> $curl_options Array of CURLOPT_* => value key pairs that is passed into curl handler.
	 * @throws HandlerException
	 */
	public function __construct(
		array $curl_options = [])
	{
		$handle = \curl_init();

		if( $handle === false ){
			throw new HandlerException("Could not initialize cURL.");
		}

		$this->curlHandle = $handle;
		$this->options = $curl_options + $this->options;
	}

	/**
	 * Execute the given request.
	 *
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 */
	public function execute(RequestInterface $request, ResponseInterface $response): ResponseInterface
	{
		// Set the default cURL options
		\curl_setopt_array(
			$this->curlHandle,
			$this->options + $this->buildCurlRequestOptions($request, $response)
		);

		if( \curl_exec($this->curlHandle) === false ){
			throw new RequestException(
				$request,
				\curl_strerror(\curl_errno($this->curlHandle)) ?? "Unknown error",
				\curl_errno($this->curlHandle)
			);
		}

		// Rewind the body before passing it back.
		if( $response->getBody()->isSeekable() ){
			$response->getBody()->rewind();
		}

		return $response;
	}

	/**
	 * Build the cURL option set for the given request.
	 *
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 * @return array
	 */
	protected function buildCurlRequestOptions(RequestInterface $request, ResponseInterface &$response): array
	{
		$curlOptions = [

			CURLOPT_HTTP_VERSION => $this->buildRequestHttpProtocolVersion($request),
			CURLOPT_CUSTOMREQUEST => $request->getMethod(),
			CURLOPT_PORT => $request->getUri()->getPort(),
			CURLOPT_URL => (string) $request->getUri(),
			CURLOPT_HTTPHEADER => $this->buildRequestHeaders($request),

			/**
			 * Callback when data is received.
			 *
			 * @param resource $handler
			 * @param string $data
			 * @return int
			 */
			CURLOPT_WRITEFUNCTION => function(CurlHandle $handler, string $data) use (&$response): int {

				return $response->getBody()->write($data);
			},

			/**
			 * Callback when headers are received.
			 *
			 * @param resource $handler
			 * @param string $header
			 * @return int
			 */
			CURLOPT_HEADERFUNCTION => function(CurlHandle $handler, string $header) use (&$response): int {

				if( \preg_match("/^HTTP\/([\d\.]+) ([\d]{3})(?: ([\w\h]+))?\R?+$/i", \trim($header), $httpResponse) ){
					$response = $response->withStatus((int) $httpResponse[2], $httpResponse[3] ?? "");
					$response = $response->withProtocolVersion($httpResponse[1]);
				}

				elseif( \preg_match("/^([\w\d\-_]+)\: (\N+)\R?+$/", \trim($header), $httpHeader) ){
					$response = $response->withAddedHeader($httpHeader[1], $httpHeader[2]);
				}

				return \strlen($header);
			}

		];

		// Set the request body (if applicable)
		if( $request->getBody()->getSize() &&
			\in_array(\strtoupper($request->getMethod()), ["POST", "PUT", "PATCH"]) ){
			$curlOptions[CURLOPT_POSTFIELDS] = $request->getBody()->getContents();
		}

		return $curlOptions;
	}

	/**
	 * Build the HTTP protocol version to use.
	 *
	 * @param RequestInterface $request
	 * @return int
	 */
	private function buildRequestHttpProtocolVersion(RequestInterface $request): int
	{
		return match($request->getProtocolVersion()) {
			"2", "2.0" => CURL_HTTP_VERSION_2,
			"1", "1.0" => CURL_HTTP_VERSION_1_0,
			default => CURL_HTTP_VERSION_1_1
		};
	}

	/**
	 * Build the processed request header values as an array of header strings.
	 *
	 * Eg:
	 * [
	 *      "Content-Type: text/plain",
	 *      "Authorization: Basic YnJlbnRAbmltYmx5LmlvOnBhc3N3b3JkCg=="
	 * ]
	 *
	 * @param RequestInterface $request
	 * @return array<string>
	 */
	private function buildRequestHeaders(RequestInterface $request): array
	{
		return \array_map(
			function(string $name, array $values): string {
				return $name . ": " . \implode(",", $values);
			},
			\array_keys($request->getHeaders()),
			$request->getHeaders()
		);
	}
}