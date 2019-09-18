<?php declare(strict_types=1);

namespace Shuttle\Handler;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;


abstract class HandlerAbstract
{
    /**
     * Execute the given Request.
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    abstract public function execute(RequestInterface $request): ResponseInterface;

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