<?php

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
}