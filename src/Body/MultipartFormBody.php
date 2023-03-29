<?php

namespace Nimbly\Shuttle\Body;

use Nimbly\Capsule\Stream\BufferStream;
use Nimbly\Shuttle\EncodingException;


class MultipartFormBody extends BufferStream implements BodyInterface
{
	/**
	 * Multi-part content type, without boundary.
	 */
	protected string $content_type = "multipart/form-data";

	/**
	 * Boundary string.
	 *
	 * @var string
	 */
	protected string $boundary;

	/**
	 * @param array<string,PartInterface> $parts
	 */
	public function __construct(array $parts)
	{
		// Create a random boundary name for each multipart request.
		$this->boundary = \uniqid("Capsule") . "Z";

		/**
		 * @var string $name
		 * @var PartInterface $part
		 */
		foreach( $parts as $name => $part ){

			/** @psalm-suppress DocblockTypeContradiction */
			if( !\is_string($name) ){
				throw new EncodingException("Please provide a name for each part of a Multipart request.");
			}

			$this->write(
				$part->getMultiPart($this->boundary, $name)
			);
		}

		$this->write("\r\n--{$this->boundary}--\r\n");
	}

	/**
	 * Get the boundary string.
	 *
	 * @return string
	 */
	public function getBoundary(): string
	{
		return $this->boundary;
	}

	/**
	 * @inheritDoc
	 */
	public function getContentType(): string
	{
		return \sprintf(
			"%s;boundary=%s",
			$this->content_type,
			$this->boundary
		);
	}
}