<?php

namespace Shuttle;

use Closure;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Shuttle\Body\BodyInterface;
use Shuttle\Handler\CurlHandler;
use Shuttle\Handler\HandlerAbstract;

class Shuttle implements ClientInterface
{
    const SHUTTLE_USER_AGENT = "Shuttle/1.0";

    /**
     * Shuttle specific options.
     *
     * @var array
     */
    private $options = [
        "handler" => null,
        "http_version" => "1.1",
        "base_url" => null,
        "headers" => [],
        "middleware" => [],
    ];

    /**
     * Compiled Middleware pipeline.
     *
     * @var \Closure
     */
    private $middlewareStack;

    /**
     * Shuttle constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);

        // Create default HTTP handler (CurlHandler) if none was provided.
        $this->options['handler'] = $this->options["handler"] ?? new CurlHandler;

        // Make sure Handler is really a Handler.
        if( ($this->options['handler'] instanceof HandlerAbstract) === false ){
            throw new \Exception("Handler option must be an instance of HandlerAbstract.");
        }

        // Compile the Middleware stack.
        $this->middlewareStack = $this->compileMiddleware(
            $this->options['middleware'],
            [$this->getHandler(), 'execute']
        );
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
     * @inheritDoc
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        // Set default HTTP version
        $request = $request->withProtocolVersion($this->options['http_version']);

        // Add in default headers to request.
        if( !empty($this->options['headers']) ){
            foreach( $this->options['headers'] as $name => $value ){
                $request = $request->withAddedHeader($name, $value);
            }
        }

        // Add in default User-Agent header if none was provided.
        if( $request->hasHeader('User-Agent') === false ){
            $request = $request->withHeader('User-Agent', self::SHUTTLE_USER_AGENT . ' PHP/' . PHP_VERSION);
        }

        return call_user_func_array($this->middlewareStack, [$request]);
    }

    /**
     * Build the middleware Lamda stack.
     *
     * @param array $layers
     * @return Closure
     */
    private function compileMiddleware(array $layers, callable $kernel): Closure
    {
        // Reverse the Middleware layers as we are essentially pushing them onto a stack.
        $layers = array_reverse($layers);

        // Create a single nested Lamda with all the Middleware.
        return array_reduce($layers, function(Closure $next, MiddlewareInterface $middleware) {

            return function($request) use ($next, $middleware){
                return $middleware->process($request, $next);
            };

        }, function($request) use ($kernel) {
            return $kernel($request);
        });
    }

    /**
     * Make a request.
     *
     * @param string $method
     * @param Uri|string $url
     * @param StreamInterface $body
     * @param array $options
     * @throws RequestException
     * @return ResponseInterface
     */
    public function request($method, $url, StreamInterface $body = null, array $options = []): ResponseInterface
    {
        // Build out URI instance
        if( $url instanceof Uri === false ){
            $url = new Uri(
                ($this->options['base_url'] ?? "") . $url
            );
        }

        // Create a new Request
        $request = (new Request)
        ->withMethod($method)
        ->withUri($url);

        if( $body ){
            $request = $request->withBody($body);

            if( $body instanceof BodyInterface ){
                $request = $request->withHeader("Content-Type", $body->getContentType());
            }
        }

        if( !empty($options['headers']) ){
            foreach( $options['headers'] as $key => $value ){
                $request = $request->withHeader($key, $value);
            }
        }

        return $this->sendRequest($request);
    }

    /**
     * Make a GET call.
     *
     * @param string $path
     * @param array $options
     * @return ResponseInterface
     */
    public function get($path, array $options = []): ResponseInterface
    {
        return $this->request("get", $path, null, $options);
    }

    /**
     * Make a POST call.
     *
     * @param string $path
     * @param StreamInterface $body
     * @param array $options
     * @return ResponseInterface
     */
    public function post($path, StreamInterface $body, array $options = []): ResponseInterface
    {
        return $this->request("post", $path, $body, $options);
    }

    /**
     * Make a PUT call.
     *
     * @param string $path
     * @param StreamInterface $body
     * @param array $options
     * @return ResponseInterface
     */
    public function put($path, StreamInterface $body, array $options = []): ResponseInterface
    {
        return $this->request("put", $path, $body, $options);
    }

    /**
     * Make a PATCH call.
     *
     * @param string $path
     * @param StreamInterface $body
     * @param array $options
     * @return ResponseInterface
     */
    public function patch($path, StreamInterface $body, array $options = []): ResponseInterface
    {
        return $this->request("patch", $path, $body, $options);
    }

    /**
     * Make a DELETE call.
     *
     * @param string $path
     * @param array $options
     * @return ResponseInterface
     */
    public function delete($path, array $options = []): ResponseInterface
    {
        return $this->request("delete", $path, null, $options);
    }

    /**
     * Make a HEAD call.
     *
     * @param string $path
     * @param array $options
     * @return ResponseInterface
     */
    public function head($path, array $options = []): ResponseInterface
    {
        return $this->request("head", $path, null, $options);
    }

    /**
     * Make an OPTIONS call.
     *
     * @param string $path
     * @param array $options
     * @return ResponseInterface
     */
    public function options($path, array $options = []): ResponseInterface
    {
        return $this->request("options", $path, null, $options);
    }
}