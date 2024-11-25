<?php

namespace Nimbly\Shuttle\Body;

use Nimbly\Capsule\Stream\ResourceStream;
use Nimbly\Shuttle\FileException;
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
	protected StreamInterface $stream;

	/**
	 * File name to use in multipart/form-data content disposition.
	 *
	 * @var string
	 */
	protected string $file_name;

	/**
	 * The file's content type.
	 *
	 * @var string
	 */
	protected string $content_type;

	/**
	 * @param StreamInterface|string $file StreamInterface instance of file or a string that is the full path of file to open for reading.
	 * @param string|null $file_name Filename to assign to content.
	 * @param string|null $content_type File mime content type.
	 * @throws \Exception
	 */
	public function __construct(StreamInterface|string $file, ?string $file_name = null, ?string $content_type = null)
	{
		if( \is_string($file)){
			$fh = \fopen($file, "r");

			if( $fh === false ){
				throw new FileException("Failed to open {$file} for reading.");
			}

			$file = new ResourceStream($fh);
		}

		$this->stream = $file;
		/**
		 * @psalm-suppress PossiblyInvalidArgument
		 */
		$this->file_name = $file_name ?? \basename($file->getMetadata("uri") ?? "file");
		$this->content_type = $content_type ?? "text/plain";
	}

	/**
	 * @inheritDoc
	 */
	public function getMultiPart(string $boundary, ?string $name = null): string
	{
		// Rewind the stream, just in case we're at the end.
		if( $this->stream->isSeekable() ){
			$this->stream->rewind();
		}

		return \sprintf(
			"\r\n--%s\r\nContent-Disposition: form-data; name=\"%s\"; filename=\"%s\"\r\nContent-Type: %s\r\nContent-Length: %s\r\n\r\n%s",
			$boundary,
			$name ?? "file",
			$this->file_name,
			$this->content_type,
			$this->stream->getSize() ?? 0,
			$this->stream->getContents()
		);
	}
}