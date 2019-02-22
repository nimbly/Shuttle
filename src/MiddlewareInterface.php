<?php declare(strict_types=1);

namespace Shuttle;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;


interface MiddlewareInterface
{
    /**
     * Middleware layer processor.
     *
     * @param RequestInterface $request
     * @param callable $next
     * @return ResponseInterface
     */
    public function process(RequestInterface $request, callable $next): ResponseInterface;
}