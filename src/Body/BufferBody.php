<?php

namespace Nimbly\Shuttle\Body;

use Nimbly\Capsule\Stream\BufferStream;

/**
 *
 * A generic string buffer body with no formatting/serialization.
 *
 * Sets the Content-Type as "text/plain" by default.
 *
 */
class BufferBody extends BufferStream implements BodyInterface, PartInterface
{
	/**
	 * Content-Type header data.
	 *
	 * E.g. application/json
	 */
	protected string $content_type = "text/plain";

	/**
	 * BufferBody constructor.
	 *
	 * @param string $data
	 * @param string|null $content_type
	 */
	public function __construct(string $data, ?string $content_type = null)
	{
		$this->buffer = $data;

		if( $content_type ){
			$this->content_type = $content_type;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getContentType(): string
	{
		return $this->content_type;
	}

	/**
	 * @inheritDoc
	 */
	public function getMultiPart(string $boundary, ?string $name = null): string
	{
		return \sprintf(
			"\r\n--%s\r\nContent-Disposition: form-data; name=\"%s\"\r\nContent-Type: %s\r\n\r\n%s",
			$boundary,
			$name ?? "form",
			$this->getContentType(),
			$this->buffer ?? ""
		);
	}
}