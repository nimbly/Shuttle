<?php declare(strict_types=1);

namespace Shuttle\Handler;

use Capsule\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class MockHandler extends HandlerAbstract
{
    /**
     * Array of pre-fab Response objects to be returned with requests.
     *
     * @var array<ResponseInterface|callable>
     */
    protected $responses = [];

    /**
     * Debug mode flag.
     *
     * @var boolean
     */
    protected $debug = false;

    /**
     * MockHandler constructor.
     *
     * Pass in an array of Response instances that will be returned. You may also
     * pass in a closure that takes the Request and must return a Response.
     *
     * @param array<Response|callable> $responses
     */
    public function __construct(array $responses)
    {
        $this->responses = $responses;
    }

    /**
     * @inheritDoc
     */
    public function execute(RequestInterface $request): ResponseInterface
    {
        if( empty($this->responses) ){
            throw new \Exception("No more responses available in MockHandler response queue.");
        }

        $response = \array_shift($this->responses);

        if( \is_callable($response) ){
            return \call_user_func($response, $request);
        }

        return $response;
    }

    /**
     * @inheritDoc
     */
    public function setDebug(bool $debug): HandlerAbstract
    {
        $this->debug = $debug;
        return $this;
    }
}