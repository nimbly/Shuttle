<?php declare(strict_types=1);

namespace Shuttle\Handler;

use Psr\Http\Message\RequestInterface;
use Shuttle\Response;

class MockHandler extends HandlerAbstract
{
    /**
     * Array of pre-fab Response objects to be returned with requests.
     *
     * @var Response[]
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
     * Pass in an array of Response instances that will be returned
     *
     * @param array $responses
     */
    public function __construct(array $responses)
    {
        $this->responses = $responses;
    }

    /**
     * @inheritDoc
     */
    public function execute(RequestInterface $request): Response
    {
        if( empty($this->responses) ){
            throw new \Exception("No more responses available in MockHandler response queue.");
        }

        return array_shift($this->responses);
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