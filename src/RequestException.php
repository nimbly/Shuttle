<?php declare(strict_types=1);

namespace Shuttle;

use Exception;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Throwable;


class RequestException extends Exception implements RequestExceptionInterface
{
    /**
     * Request instance.
     *
     * @var RequestInterface
     */
    protected $request;

    /**
     * RequestException constructor.
     *
     * @param RequestInterface $request
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(RequestInterface $request, $message, $code, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->request = $request;
    }

    /**
     * Get the request instance.
     *
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}