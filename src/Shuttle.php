<?php

namespace Nimbly\Shuttle;

use Closure;
use Nimbly\Capsule\Factory\RequestFactory;
use Nimbly\Capsule\Factory\ResponseFactory;
use Nimbly\Capsule\Factory\StreamFactory;
use Nimbly\Capsule\Factory\UriFactory;
use Nimbly\Shuttle\Body\BodyInterface;
use Nimbly\Shuttle\Handler\CurlHandler;
use Nimbly\Shuttle\Handler\HandlerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

class Shuttle implements ClientInterface
{
	const SHUTTLE_USER_AGENT = "Shuttle/2.0";

	protected HandlerInterface $handler;
	protected RequestFactoryInterface $requestFactory;
	protected ResponseFactoryInterface $responseFactory;
	protected StreamFactoryInterface $streamFactory;
	protected UriFactoryInterface $uriFactory;
	protected Closure $middleware;

	/**
	 * @param HandlerInterface|null $handler
	 * @param string|null $base_url
	 * @param array<string,string> $headers
	 * @param array<MiddlewareInterface> $middleware
	 * @param string $http_version
	 * @param RequestFactoryInterface|null $requestFactory
	 * @param ResponseFactoryInterface|null $responseFactory
	 * @param StreamFactoryInterface|null $streamFactory
	 * @param UriFactoryInterface|null $uriFactory
	 */
	public function __construct(
		?HandlerInterface $handler = null,
		protected ?string $base_url = null,
		protected array $headers = [],
		array $middleware = [],
		protected string $http_version = "1.1",
		?RequestFactoryInterface $requestFactory = null,
		?ResponseFactoryInterface $responseFactory = null,
		?StreamFactoryInterface $streamFactory = null,
		?UriFactoryInterface $uriFactory = null,
	)
	{
		if( empty($handler) ){
			$handler = new CurlHandler;
		}

		if( empty($requestFactory) ){
			$requestFactory = new RequestFactory;
		}

		if( empty($responseFactory) ){
			$responseFactory = new ResponseFactory;
		}

		if( empty($streamFactory) ){
			$streamFactory = new StreamFactory;
		}

		if( empty($uriFactory) ){
			$uriFactory = new UriFactory;
		}

		$this->handler = $handler;
		$this->requestFactory = $requestFactory;
		$this->responseFactory = $responseFactory;
		$this->uriFactory = $uriFactory;
		$this->streamFactory = $streamFactory;
		$this->middleware = $this->compileMiddleware(
			$middleware,
			function(RequestInterface $request): ResponseInterface {
				return $this->handler->execute(
					$request,
					$this->responseFactory->createResponse(200)
				);
			}
		);
	}

	/**
	 * Compile the middleware chain.
	 *
	 * @param array<MiddlewareInterface> $layers
	 * @param callable $kernel
	 * @return Closure
	 */
	private function compileMiddleware(array $layers, callable $kernel): Closure
	{
		// Reverse the Middleware layers as we are essentially pushing them onto a stack.
		$layers = \array_reverse($layers);

		// Create a single chained Lamda with all the Middleware layers being passed on to the next.
		return \array_reduce(
			$layers,
			function(callable $next, MiddlewareInterface $middleware): Closure {
				return function(RequestInterface $request) use ($next, $middleware): ResponseInterface {
					return $middleware->process($request, $next);
				};
			},
			function(RequestInterface $request) use ($kernel): ResponseInterface {
				return \call_user_func($kernel, $request);
			}
		);
	}

	/**
	 * Get the HTTP handler.
	 *
	 * @return HandlerInterface
	 */
	public function getHandler(): HandlerInterface
	{
		return $this->handler;
	}

	/**
	 * Make a Request instance.
	 *
	 * @param string $method
	 * @param string|UriInterface $uri
	 * @param string|StreamInterface|null $body
	 * @param array<string,string> $headers
	 * @param string $http_version
	 * @return RequestInterface
	 */
	public function makeRequest(
		string $method,
		string|UriInterface $uri,
		string|StreamInterface|null $body = null,
		array $headers = [],
		string $http_version = "1.1"): RequestInterface
	{
		// Create a new Request
		$request = $this->requestFactory->createRequest(
			$method,
			\is_string($uri) ? $this->uriFactory->createUri(($this->base_url ?? "") . $uri) : $uri
		);

		$request = $request->withProtocolVersion($http_version);

		if( $body instanceof StreamInterface ){
			$request = $request->withBody($body);
		}
		else {
			$request = $request->withBody($this->streamFactory->createStream((string) $body));
		}

		foreach( \array_merge($this->headers, $headers) as $name => $value ){
			$request = $request->withAddedHeader($name, $value);
		}

		// Add Content-Type header
		if( $request->getBody()->getSize() ){
			if( $body instanceof BodyInterface && $request->hasHeader("Content-Type") === false ){
				$request = $request->withHeader("Content-Type", $body->getContentType());
			}

			if( $request->hasHeader("Content-Length") === false ){
				$request = $request->withHeader("Content-Length", (string) $request->getBody()->getSize());
			}
		}

		// Add in default User-Agent header if none was provided.
		if( $request->hasHeader("User-Agent") === false ){
			$request = $request->withHeader("User-Agent", self::SHUTTLE_USER_AGENT . " PHP/" . PHP_VERSION);
		}

		return $request;
	}

	/**
	 * @inheritDoc
	 */
	public function sendRequest(RequestInterface $request): ResponseInterface
	{
		/**
		 *
		 * Send the request through the Middleware chain.
		 *
		 */
		return \call_user_func(
			$this->middleware,
			$request
		);
	}

	/**
	 * Make a request.
	 *
	 * @param string $method
	 * @param string|UriInterface $uri
	 * @param string|StreamInterface|null $body
	 * @param array<array-key,string> $headers
	 * @param string $http_version
	 * @throws RequestException
	 * @return ResponseInterface
	 */
	public function request(
		string $method,
		string|UriInterface $uri,
		string|StreamInterface|null $body = null,
		array $headers = [],
		string $http_version = "1.1"): ResponseInterface
	{
		return $this->sendRequest(
			$this->makeRequest(
				$method,
				$uri,
				$body,
				$headers,
				$http_version
			)
		);
	}

	/**
	 * Make a GET call.
	 *
	 * @param string $path
	 * @param array<array-key,mixed> $headers
	 * @return ResponseInterface
	 */
	public function get(string $path, array $headers = []): ResponseInterface
	{
		return $this->request("get", $path, null, $headers);
	}

	/**
	 * Make a POST call.
	 *
	 * @param string $path
	 * @param StreamInterface $body
	 * @param array<array-key,mixed> $headers
	 * @return ResponseInterface
	 */
	public function post(string $path, StreamInterface $body, array $headers = []): ResponseInterface
	{
		return $this->request("post", $path, $body, $headers);
	}

	/**
	 * Make a PUT call.
	 *
	 * @param string $path
	 * @param StreamInterface $body
	 * @param array<array-key,mixed> $headers
	 * @return ResponseInterface
	 */
	public function put(string $path, StreamInterface $body, array $headers = []): ResponseInterface
	{
		return $this->request("put", $path, $body, $headers);
	}

	/**
	 * Make a PATCH call.
	 *
	 * @param string $path
	 * @param StreamInterface $body
	 * @param array<array-key,mixed> $headers
	 * @return ResponseInterface
	 */
	public function patch(string $path, StreamInterface $body, array $headers = []): ResponseInterface
	{
		return $this->request("patch", $path, $body, $headers);
	}

	/**
	 * Make a DELETE call.
	 *
	 * @param string $path
	 * @param array<array-key,mixed> $headers
	 * @return ResponseInterface
	 */
	public function delete(string $path, array $headers = []): ResponseInterface
	{
		return $this->request("delete", $path, null, $headers);
	}

	/**
	 * Make a HEAD call.
	 *
	 * @param string $path
	 * @param array<array-key,mixed> $headers
	 * @return ResponseInterface
	 */
	public function head(string $path, array $headers = []): ResponseInterface
	{
		return $this->request("head", $path, null, $headers);
	}

	/**
	 * Make an OPTIONS call.
	 *
	 * @param string $path
	 * @param array<array-key,mixed> $headers
	 * @return ResponseInterface
	 */
	public function options(string $path, array $headers = []): ResponseInterface
	{
		return $this->request("options", $path, null, $headers);
	}
}