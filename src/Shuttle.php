<?php declare(strict_types=1);

namespace Shuttle;

\define("SHUTTLE_USER_AGENT", "Shuttle/1.0");

use Capsule\Request;
use Closure;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Shuttle\Body\BodyInterface;
use Shuttle\Handler\CurlHandler;
use Shuttle\Handler\HandlerAbstract;

class Shuttle implements ClientInterface
{
    /**
     * Shuttle specific options.
     *
     * @var array
     */
    private $options = [

        /**
         * Handler to use for calls.
         *
         * Defaults to CurlHandler.
         */
        "handler" => null,

        /**
         * HTTP protocol version to use for calls.
         *
         * Defaults to "1.1".
         */
        "http_version" => "1.1",

        /**
         * Base URL to prepend to request calls. This base URL is only prepended
         * when using the "request" method (or any of the built-in HTTP method
         * calls: get, post, put, etc.).
         *
         * Defaults to null.
         */
        "base_url" => null,

        /**
         * Set of key => value pairs to include as default headers with
         * request calls. Headers are only added when using the "request"
         * method (or any of the built-in HTTP method calls: get, post, put, etc.).
         *
         * Defaults to empty array.
         */
        "headers" => [],

        /**
         * Middleware instances to run in the middleware pipeline. Each element
         * should be an instance of Shuttle\MiddlewareInterface. Middleware
         * are executed in the order provided in the array.
         *
         * Defaults to empty array.
         */
        "middleware" => [],

        /**
         * Enable or disable debug mode.
         * Debug mode will print verbose connection, request, and response data to STDOUT.
         *
         * Defaults to false.
         */
        "debug" => false,
    ];

    /**
     * Compiled Middleware chain
     *
     * @var Closure
     */
    private $middlewareChain;

    /**
     * Shuttle constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        // Merge in user supplied options.
        $this->options = \array_merge($this->options, $options);

        // Create default HTTP handler (CurlHandler) if none was provided.
        $this->options['handler'] = $this->options["handler"] ?? new CurlHandler;

        // Make sure handler is really a Handler.
        if( ($this->options['handler'] instanceof HandlerAbstract) === false ){
            throw new \Exception("Handler option must be an instance of HandlerAbstract.");
        }

        // Enable debug mode on handler.
        if( $this->options['debug'] ){
            $this->getHandler()->setDebug($this->options['debug'] === true);
        }

        // Build the middleware chain.
        $this->middlewareChain = $this->compileMiddleware(
            $this->options['middleware'] ?? [],
            [$this->getHandler(), 'execute']
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
        return \array_reduce($layers, function(Closure $next, MiddlewareInterface $middleware): Closure {

            return function(RequestInterface $request) use ($next, $middleware): ResponseInterface {
                return $middleware->process($request, $next);
            };

        }, function(RequestInterface $request) use ($kernel): ResponseInterface {
            return \call_user_func($kernel, $request);
        });
    }

    /**
     * Get the HTTP handler.
     *
     * @return HandlerAbstract
     */
    public function getHandler(): HandlerAbstract
    {
        return $this->options['handler'];
    }

    /**
     * Make a Request instance.
     *
     * @param string $method
     * @param UriInterface|string $uri
     * @param StreamInterface $body
     * @param array<string, mixed>	 $options
     * @return RequestInterface
     */
    public function makeRequest(string $method, $uri, StreamInterface $body = null, array $options = []): RequestInterface
    {
        // Create a new Request
        $request = new Request(
			$method,
			\is_string($uri) ? ($this->options['base_url'] ?? "" . $uri) : $uri,
			$body,
			\array_merge($this->options['headers'] ?? [], $options['headers'] ?? []),
			$options['http_version'] ?? $this->options['http_version'] ?? '1.1'
		);

        // Add Content-Type header
        if( $body ){
            if( $body instanceof BodyInterface &&
                $request->hasHeader('Content-Type') === false ){
                $request = $request->withHeader("Content-Type", $body->getContentType());
            }

            if( $request->hasHeader('Content-Length') === false ){
                $request = $request->withHeader("Content-Length", (string) $body->getSize());
            }
		}

		// Add in default User-Agent header if none was provided.
		if( $request->hasHeader('User-Agent') === false ){
			$request = $request->withHeader('User-Agent', SHUTTLE_USER_AGENT . ' PHP/' . PHP_VERSION);
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
        return \call_user_func($this->middlewareChain, $request);
    }

    /**
     * Make a request.
     *
     * @param string $method
     * @param UriInterface|string $uri
     * @param StreamInterface $body
     * @param array<string, mixed> $options
     * @throws RequestException
     * @return ResponseInterface
     */
    public function request(string $method, $uri, StreamInterface $body = null, array $options = []): ResponseInterface
    {
        return $this->sendRequest(
            $this->makeRequest($method, $uri, $body, $options)
        );
    }

    /**
     * Make a GET call.
     *
     * @param string $path
     * @param array<string, mixed> $options
     * @return ResponseInterface
     */
    public function get(string $path, array $options = []): ResponseInterface
    {
        return $this->request("get", $path, null, $options);
    }

    /**
     * Make a POST call.
     *
     * @param string $path
     * @param StreamInterface $body
     * @param array<string, mixed> $options
     * @return ResponseInterface
     */
    public function post(string $path, StreamInterface $body, array $options = []): ResponseInterface
    {
        return $this->request("post", $path, $body, $options);
    }

    /**
     * Make a PUT call.
     *
     * @param string $path
     * @param StreamInterface $body
     * @param array<string, mixed> $options
     * @return ResponseInterface
     */
    public function put(string $path, StreamInterface $body, array $options = []): ResponseInterface
    {
        return $this->request("put", $path, $body, $options);
    }

    /**
     * Make a PATCH call.
     *
     * @param string $path
     * @param StreamInterface $body
     * @param array<string, mixed> $options
     * @return ResponseInterface
     */
    public function patch(string $path, StreamInterface $body, array $options = []): ResponseInterface
    {
        return $this->request("patch", $path, $body, $options);
    }

    /**
     * Make a DELETE call.
     *
     * @param string $path
     * @param array<string, mixed> $options
     * @return ResponseInterface
     */
    public function delete(string $path, array $options = []): ResponseInterface
    {
        return $this->request("delete", $path, null, $options);
    }

    /**
     * Make a HEAD call.
     *
     * @param string $path
     * @param array<string, mixed> $options
     * @return ResponseInterface
     */
    public function head(string $path, array $options = []): ResponseInterface
    {
        return $this->request("head", $path, null, $options);
    }

    /**
     * Make an OPTIONS call.
     *
     * @param string $path
     * @param array<string, mixed> $options
     * @return ResponseInterface
     */
    public function options(string $path, array $options = []): ResponseInterface
    {
        return $this->request("options", $path, null, $options);
    }
}