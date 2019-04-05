<?php declare(strict_types=1);

namespace Shuttle\Handler;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Capsule\Response;
use Capsule\Stream\FileStream;
use Shuttle\RequestException;

class StreamContextHandler extends HandlerAbstract
{
    /**
     * Default stream handler options.
     *
     * @var array
     */
    protected $options = [
        'follow_location' => 1,
        'request_fulluri' => false,
        'max_redirects' => 10,
        'ignore_errors' => true,
        'timeout' => 120,
    ];

    /**
     * Debug mode flag.
     *
     * @var boolean
     */
    protected $debug = false;

    /**
     * StreamContextHandler constructor.
     *
     * @param array $options Array of HTTP stream context key => value pairs. See http://php.net/manual/en/context.http.php for list of available options.
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * @inheritDoc
     */
    public function setDebug(bool $debug): HandlerAbstract
    {
        $this->debug = $debug;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function execute(RequestInterface $request): Response
    {
        // Set the context options
        $contextOptions = array_merge($this->options, [
            'protocol_version' => $request->getProtocolVersion(),
            'method' => $request->getMethod(),
            'header' => $this->buildRequestHeaders($request->getHeaders()),
            'content' => $request->getBody() ? $request->getBody()->getContents() : null,
        ]);

        // Build the HTTP stream
        $stream = $this->buildStream($request, ['http' => $contextOptions]);
        
        return $this->createResponse($stream);
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
     * Build the stream.
     *
     * @param RequestInterface $request
     * @param array $contextOptions
     * @throws RequestException
     * @return StreamInterface
     */
    public function buildStream(RequestInterface $request, array $contextOptions)
    {
        if( $this->debug ){
            $params = [
                "notification" => [$this, "debug"]
            ];
        }

        $context = stream_context_create($contextOptions, $params ?? []);

        if( ($stream = @fopen((string) $request->getUri(), 'r', false, $context)) === false ){
            
            $error = error_get_last();

            throw new RequestException($request, $error["message"] ?? "Failed to open stream", $error["code"] ?? -1);

        }

        return new FileStream($stream);
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

        // Grab the headers from the Stream meta data
        $headers = $response->getBody()->getMetadata('wrapper_data') ?? [];

        // Process the headers
        foreach( $headers as $header ){
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

    /**
     * Debug request and response.
     *
     * @param int $notification_code
     * @param int $severity
     * @param string $message
     * @param int $message_code
     * @param int $bytes_transferred
     * @param int $bytes_max
     * @return void
     */
    private function debug($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max): void
    {
        switch( $notification_code ){

            case STREAM_NOTIFY_CONNECT:
                $debug = "Connected";
                break;

            case STREAM_NOTIFY_RESOLVE:
                $debug = "Resolve: {$message}";
                break;

            case STREAM_NOTIFY_AUTH_REQUIRED:
                $debug = "Auth required: {$message}";
                break;

            case STREAM_NOTIFY_COMPLETED:
                $debug = "Completed: {$message}";
                break;

            case STREAM_NOTIFY_FAILURE:
                $debug = "Failure: {$message}";
                break;

            case STREAM_NOTIFY_AUTH_RESULT:
                $debug = "Auth result: {$message}";
                break;

            case STREAM_NOTIFY_REDIRECTED:
                $debug = "Redirect: {$message}";
                break;


            case STREAM_NOTIFY_FILE_SIZE_IS:
                $debug = "Content size: {$bytes_max}";
                break;

            case STREAM_NOTIFY_MIME_TYPE_IS:
                $debug = "Content mime-type: {$message}";
                break;

            case STREAM_NOTIFY_PROGRESS:
                $debug = "Transfered: {$bytes_transferred}";
                break;

            default:
                $debug = "Foo";
        }

        $preamble = json_encode([
            "noitification_code" => $notification_code,
            "severity" => $severity,
            "message" => $message,
            "message_code" => $message_code,
            "bytes_transferred" => $bytes_transferred,
            "bytes_max" => $bytes_max,
        ]);

        echo "{$preamble}\n{$debug}\n";
    } 
}