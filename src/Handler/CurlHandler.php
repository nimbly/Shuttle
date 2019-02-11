<?php

namespace Shuttle\Handler;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Shuttle\RequestException;
use Shuttle\Response;
use Shuttle\Shuttle;
use Shuttle\Stream\FileStream;

class CurlHandler extends HandlerAbstract
{
    /**
     * Maximum amount of memory (in bytes) to use before swapping
     * response body to disk.
     * 
     * Defaults to 2097152 bytes (2MB).
     *
     * @var integer
     */
    protected $maxResponseBodyMemory = 2097152;

    /**
     * Set of default options.
     *
     * @var array
     */
    private $options = [

        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_HEADER => false,
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_CONNECTTIMEOUT => 120,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        CURLOPT_VERBOSE => false,

    ];

    /**
     * Curl Resource instance.
     *
     * @var resource
     */
    private $curlResource;

    /**
     * CurlHandler constructor.
     * 
     * @param array $options Array of CURLOPT_* => value key pairs that is passed into curl handler.
     * 
     */
    public function __construct(array $options = [])
    {
        $this->curlResource = curl_init();
        $this->options += $options;
    }

    /**
     * Set the maximum amount of memory the response body may consume before
     * swapping to disk. Defaults to 2097152 bytes (2MB).
     *
     * @param integer $bytes
     * @return CurlHandler
     */
    public function setMaxResponseBodyMemory(int $bytes): CurlHandler
    {
        $this->maxResponseBodyMemory = $bytes;
        return $this;
    }

    /**
     * Enable/disable cURL verbosity (debug) mode.
     *
     * @param boolean $verbose
     * @return CurlHandler
     */
    public function setVerbose(bool $verbose = true): CurlHandler
    {
        $this->options[CURLOPT_VERBOSE] = $verbose;
        return $this;
    }

    /**
     * Execute the given request.
     *
     * @param RequestInterface $request
     * @return Response
     */
    public function execute(RequestInterface $request): Response
    {
        $handler = $this->curlResource;

        // Create a new Response with php://temp body.
        $response = (new Response)->withBody(
            $this->makeResponseBodyStream()
        );

        // Set the default cURL options
        curl_setopt_array($handler, $this->options + [

            CURLOPT_HTTP_VERSION => $this->buildRequestHttpProtocolVersion($request),
            CURLOPT_CUSTOMREQUEST => $request->getMethod(),
            CURLOPT_PORT => $request->getUri()->getPort(),
            CURLOPT_URL => (string) $request->getUri(),
            CURLOPT_HTTPHEADER => $this->buildRequestHeaderValues($request),
            CURLOPT_WRITEFUNCTION => function($handler, $data) use (&$response): int {

                return $response->getBody()->write($data);

            },

            CURLOPT_HEADERFUNCTION => function($handler, $header) use (&$response): int {

                if( preg_match("/^HTTP\/([\d\.]+) ([\d]{3})(?: ([\w\h]+))?\R?+$/i", trim($header), $httpResponse) ){
                    $response = $response->withStatus((int) $httpResponse[2], $httpResponse[3] ?? "");
                    $response = $response->withProtocolVersion($httpResponse[1]);
                }

                elseif( preg_match("/^([\w\-]+)\: (\N+)\R?+$/", trim($header), $httpHeader) ){
                    $response = $response->withAddedHeader($httpHeader[1], $httpHeader[2]);
                }

                return strlen($header);

            }

        ]);

        // Set the request body (if applicable)
        if( $request->getBody() &&
            in_array($request->getMethod(), ["POST", "PUT", "PATCH"]) ){

            curl_setopt($handler, CURLOPT_POSTFIELDS, $request->getBody()->getContents());
        }

        // Attempt to execute the request
        if( curl_exec($handler) === false ){
            throw new RequestException($request, curl_strerror(curl_errno($handler)), curl_errno($handler));
        }

        // Rewind the body before passing it back.
        if( $response->getBody()->isSeekable() ){
            $response->getBody()->rewind();
        }

        return $response;
    }

    /**
     * Make the php://temp response stream.
     *
     * @return StreamInterface
     */
    private function makeResponseBodyStream(): StreamInterface
    {
        return new FileStream(
            fopen("php://temp/maxmemory:{$this->maxResponseBodyMemory}", "w+")
        );
    }

    /**
     * Build the HTTP protocol version to use.
     *
     * @param RequestInterface $request
     * @return int
     */
    private function buildRequestHttpProtocolVersion(RequestInterface $request): int
    {
        $version = $request->getProtocolVersion();

        if( $version == 2.0 ){
            return CURL_HTTP_VERSION_2;
        }

        elseif( $version == 1.0 ){
            return CURL_HTTP_VERSION_1_0;
        }

        else {
            return CURL_HTTP_VERSION_1_1;
        }
    }

    /**
     * Get the processed header values.
     *
     * @param RequestInterface $request
     * @return array
     */
    private function buildRequestHeaderValues(RequestInterface $request): array
    {
        $headers = [];

        // Process the request specific headers.
        foreach( $request->getHeaders() as $name => $values ){
            foreach( $values as $value ){
                $headers[] = "{$name}: {$value}";
            }
        }

        return $headers;
    }
}