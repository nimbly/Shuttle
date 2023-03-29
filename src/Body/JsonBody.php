<?php

namespace Nimbly\Shuttle\Body;

use Nimbly\Shuttle\EncodingException;

/**
 * Formats an associative array as a JSON encoded string.
 *
 * Sets the Content-Type as "application/json" by default.
 */
class JsonBody extends BufferBody
{
	/**
	 * @inheritDoc
	 */
	protected string $content_type = "application/json";

	/**
	 * @param array $data
	 * @param string|null $content_type
	 */
	public function __construct(array $data, ?string $content_type = null)
	{
		$json = \json_encode($data, JSON_UNESCAPED_SLASHES);

		if( $json === false ){
			throw new EncodingException("Failed to encode body as JSON.");
		}

		$this->buffer = $json;

		if( $content_type ){
			$this->content_type = $content_type;
		}
	}
}