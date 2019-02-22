<?php declare(strict_types=1);

namespace Shuttle;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Shuttle\Stream\BufferStream;
use Shuttle\Uri;


class Request extends MessageAbstract implements RequestInterface
{
    /**
     * HTTP method
     *
     * @var string
     */
    protected $method;

    /**
     * Request URI
     *
     * @var UriInterface
     */
    protected $uri;

    /**
     * Request target form
     *
     * @var string
     */
    protected $requestTarget;

    /**
     * @param string $method
     * @param UriInterface|string $uri
     * @param StreamInterface|string $body
     * @param array $headers
     * @param string $httpVersion
     */
    public function __construct($method = null, $uri = null, $body = null, array $headers = null, $httpVersion = "1.1")
    {
        if( $method ){
            $this->method = strtoupper($method);
        }

        if( $uri ){
            $this->uri = $uri instanceof UriInterface ? $uri : new Uri($uri);
        }

        if( $body ){
            $this->body = $body instanceof StreamInterface ? $body : new BufferStream((string) $body);
        }

        if( $headers ){
            $this->setHeaders($headers);
        }

        $this->version = $httpVersion;
    }

    /**
     * Make a new Request instance.
     *
     * @param string $method
     * @param UriInterface $uri
     * @param StreamInterface $body
     * @param array $headers
     * @param Request
     */
    public static function make(string $method, UriInterface $uri, StreamInterface $body = null, array $headers = null, string $version = "1.1"): Request
    {
        $request = (new static)
        ->withMethod($method)
        ->withUri($uri)
        ->withBody($body ?? new BufferStream)
        ->withProtocolVersion($version);

        if( $headers ){
            foreach( $headers as $name => $value ){
                $request = $request->withAddedHeader($name, $value);
            }
        }

        return $request;
    }

    /**
     * @inheritDoc
     */
    public function withMethod($method) : Request
    {
        $instance = clone $this;
        $instance->method = strtoupper($method);
        return $instance;
    }

    /**
     * @inheritDoc
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @inheritDoc
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @inheritDoc
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $instance = clone $this;
        $instance->uri = $uri;
        return $instance;
    }

    /**
     * @inheritDoc
     */
    public function getRequestTarget()
    {
        return $this->requestTarget;
    }

    /**
     * @inheritDoc
     */
    public function withRequestTarget($requestTarget)
    {
        $instance = clone $this;
        $instance->requestTarget = $requestTarget;
        return $instance;
    }
}