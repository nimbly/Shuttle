<?php declare(strict_types=1);

namespace Shuttle\Body;

use Capsule\Stream\ResourceStream;
use Psr\Http\Message\StreamInterface;

/**
 * @package Shuttle\Body
 *
 * Useable only within in a MultpartFormBody.
 *
 */
class FileUploadBody extends BufferBody
{
    /**
     * File contents to upload.
     *
     * @var StreamInterface
     */
    protected $stream;

    /**
     * File name to use in multipart/form-data content disposition.
     *
     * @var string
     */
    protected $fileName;

    /**
     * The file's content type.
     *
     * @var string
     */
    protected $fileContentType;

    /**
     * FileUpload constructor
     *
     * @param StreamInterface|string $file StreamInterface instance of file or the full path of file to open.
     * @param string|null $fileName Filename to assign to content.
     * @param string|null $contentType File mime content type.
     */
    public function __construct($file, ?string $fileName = null, string $contentType = null)
    {
        if( ($file instanceof StreamInterface) === false ){
            $this->stream = new ResourceStream(
                \fopen($file, "r")
            );

            $this->fileName = $fileName ?? \basename($file);
            $this->fileContentType = $contentType ?? \mime_content_type($file);
        }

        else {
			$this->stream = $file;
			/**
			 * @psalm-suppress PossiblyInvalidArgument
			 */
            $this->fileName = $fileName ?? \basename($file->getMetadata('uri') ?? "file");
            $this->fileContentType = $contentType ?? 'text/plain';
        }
    }

    /**
     * @inheritDoc
     */
    public function getMultiPart(string $boundary, string $name): string
    {
        // Rewind the stream, just in case we're at the end.
        if( $this->stream->isSeekable() ){
            $this->stream->rewind();
        }

        // Build out multi-part
        $multipart = "\r\n--{$boundary}\r\n";
        $multipart .= "Content-Disposition: form-data; name=\"{$name}\"; filename=\"{$this->fileName}\"\r\n";
        $multipart .= "Content-Type: {$this->fileContentType}\r\n";
        $multipart .= "Content-Length: {$this->stream->getSize()}\r\n\r\n";
        $multipart .= $this->stream->getContents();

        return $multipart;
    }
}