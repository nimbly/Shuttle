<?php declare(strict_types=1);

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

    /**
     * Enable or disable debug mode for the handler.
     * 
     * Debug mode will print additional connection, request, and response information to STDOUT.
     * 
     * @param boolean $debug
     * @return HandlerAbstract
     */
    abstract public function setDebug(bool $debug): HandlerAbstract;
}