<?php

namespace Shuttle\Handler;

use Shuttle\Response;
use Psr\Http\Message\RequestInterface;


abstract class HandlerAbstract
{
    /**
     * Execute the given Request.
     *
     * @param RequestInterface $request
     * @return Response
     */
    abstract public function execute(RequestInterface $request): Response;
}