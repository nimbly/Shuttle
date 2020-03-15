<?php declare(strict_types=1);

namespace Shuttle\Handler;

use Capsule\Response;
use Capsule\Stream\ResourceStream;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
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
        $this->options = \array_merge($this->options, $options);
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
    public function execute(RequestInterface $request): ResponseInterface
    {
        $stream = $this->buildStream($request, ['http' => $this->buildHttpContext($request)]);

        return $this->createResponse($stream);
    }

    /**
     * Build the HTTP context options.
     *
     * @param RequestInterface $request
     * @return array<string, mixed>
     */
    private function buildHttpContext(RequestInterface $request): array
    {
        return \array_merge($this->options, [
            'protocol_version' => $request->getProtocolVersion(),
            'method' => $request->getMethod(),
            'header' => $this->buildRequestHeaders($request->getHeaders()),
            'content' => $request->getBody() ? $request->getBody()->getContents() : null
        ]);
    }

    /**
     * Build the request headers.
     *
     * @param array $requestHeaders
     * @return array<string>
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
     * @param array<string, array<string, mixed>> $contextOptions
     * @throws RequestException
     * @return StreamInterface
     */
    private function buildStream(RequestInterface $request, array $contextOptions): StreamInterface
    {
        if( $this->debug ){
            $params = [
                "notification" => [$this, "debug"]
            ];
        }

        $context = \stream_context_create($contextOptions, $params ?? []);

        if( ($stream = @\fopen((string) $request->getUri(), 'r', false, $context)) === false ){

            $error = \error_get_last();

            throw new RequestException($request, $error["message"] ?? "Failed to open stream", $error["code"] ?? -1);

        }

        return new ResourceStream($stream);
    }

    /**
     * Create the Response object from the Stream.
     *
     * @param StreamInterface $stream
     * @return ResponseInterface
     */
    private function createResponse(StreamInterface $stream): ResponseInterface
    {
		$response = new Response(200);

        // Grab the headers from the Stream meta data
        $responseHeaders = $stream->getMetadata('wrapper_data') ?? [];

        // Process the headers
        foreach( $responseHeaders as $header ){
            if( \preg_match("/^HTTP\/([\d\.]+) ([\d]{3})(?: ([\w\h]+))?\R?+$/i", \trim($header), $httpResponse) ){
				$response = $response->withProtocolVersion($httpResponse[1])
				->withStatus((int) $httpResponse[2], $httpResponse[3]);
            }

            elseif( \preg_match("/^([\w\-]+)\: (\N+)\R?+$/", \trim($header), $httpHeader) ){
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
     * @param string|null $message
     * @param int $message_code
     * @param int $bytes_transferred
     * @param int $bytes_max
     * @return void
     */
    private function debug(int $notification_code, int $severity, ?string $message, int $message_code, int $bytes_transferred, int $bytes_max): void
    {
        switch( $notification_code ){

            case STREAM_NOTIFY_CONNECT:
                $notification = "CONNECTED";
                break;

            case STREAM_NOTIFY_RESOLVE:
                $notification = "RESOLVED";
                break;

            case STREAM_NOTIFY_AUTH_REQUIRED:
                $notification = "AUTH REQUIRED";
                break;

            case STREAM_NOTIFY_COMPLETED:
                $notification = "COMPLETED";
                break;

            case STREAM_NOTIFY_FAILURE:
                $notification = "FAILURE";
                break;

            case STREAM_NOTIFY_AUTH_RESULT:
                $notification = "AUTH RESULT";
                break;

            case STREAM_NOTIFY_REDIRECTED:
                $notification = "REDIRECT";
                break;

            case STREAM_NOTIFY_FILE_SIZE_IS:
                $notification = "FILE SIZE";
                break;

            case STREAM_NOTIFY_MIME_TYPE_IS:
                $notification = "MIME TYPE";
                break;

            case STREAM_NOTIFY_PROGRESS:
                $notification = "PROGRESS";
                break;

            default:
                $notification = "UNKNOWN";
        }

		echo
			"************\n" .
            "Notification: {$notification}\n" .
            "Severity: {$severity}\n" .
            "Message: " . \trim($message ?? "") . "\n" .
            //"Message code: {$message_code}\n" .
			"Bytes Transfered: {$bytes_transferred}\n" .
			"************\n\n";
    }
}