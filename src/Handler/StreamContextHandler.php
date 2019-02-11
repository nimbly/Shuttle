<?php

namespace Shuttle\Handler;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Shuttle\Response;
use Shuttle\Stream\FileStream;

class StreamContextHandler extends HandlerAbstract
{
    /**
     * @inheritDoc
     */
    public function execute(RequestInterface $request): Response
    {
        // Set the context options
        $contextOptions = [
            'http' => [
                'protocol_version' => $request->getProtocolVersion(),
                'method' => $request->getMethod(),
                'header' => $this->buildRequestHeaders($request->getHeaders()),
                'request_fulluri' => false,
                'max_redirects' => 10,
                'ignore_errors' => true,
                'timeout' => 120,
                'content' => $request->getBody() ? $request->getBody()->getContents() : null,
            ]
        ];

        // Build the HTTP stream
        $stream = $this->buildStream($request->getUri(), $contextOptions);

        return $this->createResponse($stream);
    }

    /**
     * Build the stream.
     *
     * @param UriInterface $uri
     * @param array $options
     * @return StreamInterface
     */
    public function buildStream(UriInterface $uri, array $options)
    {
        $context = stream_context_create($options, [$this, 'debug']);

        return new FileStream(fopen((string) $uri, 'r', false, $context));
    }

    /**
     * Build the request headers.
     *
     * @param array $requestHeaders
     * @return array
     */
    private function buildRequestHeaders(array $requestHeaders): array
    {
        $headers = [];

        foreach( $requestHeaders as $key => $values ){
            foreach( $values as $value ){
                $headers[] = "{$key}: {$value}";
            }
        }

        return $headers;
    }

    /**
     * Create the Response object from the Stream.
     *
     * @param StreamInterface $stream
     * @return Response
     */
    private function createResponse(StreamInterface $stream): Response
    {
        $response = new Response;

        $response = $response->withBody($stream);

        // Process the headers
        foreach( $response->getBody()->getMetadata('wrapper_data') as $header ){
            if( preg_match("/^HTTP\/([\d\.]+) ([\d]{3})(?: ([\w\h]+))?\R?+$/i", trim($header), $httpResponse) ){
                $response = $response->withStatus((int) $httpResponse[2], $httpResponse[3] ?? "");
                $response = $response->withProtocolVersion($httpResponse[1]);
            }
    
            elseif( preg_match("/^([\w\-]+)\: (\N+)\R?+$/", trim($header), $httpHeader) ){
                $response = $response->withAddedHeader($httpHeader[1], $httpHeader[2]);
            }
        }
        
        return $response;
    }

    protected function debug($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max)
    {
        echo "Received notification.";

        switch( $notification_code ){

            case STREAM_NOTIFY_RESOLVE:
            case STREAM_NOTIFY_AUTH_REQUIRED:
            case STREAM_NOTIFY_COMPLETED:
            case STREAM_NOTIFY_FAILURE:
            case STREAM_NOTIFY_AUTH_RESULT:
                //var_dump($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max);
                break;

            case STREAM_NOTIFY_REDIRECTED:
                $debug = "Redirect: {$message}";
                break;

            case STREAM_NOTIFY_CONNECT:
                $debug = "Connected: {$message}";
                break;

            case STREAM_NOTIFY_FILE_SIZE_IS:
                $debug = "Content size: {$bytes_max}";
                break;

            case STREAM_NOTIFY_MIME_TYPE_IS:
                $debug = "Content mime-type: {$message}";
                break;

            case STREAM_NOTIFY_PROGRESS:
                $kb = round($bytes_transfered / 1024, 2);
                $debug = "Transfered: {$kb}";
                break;
        }

        echo $debug;
    } 
}