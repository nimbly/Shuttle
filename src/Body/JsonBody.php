<?php

namespace Nimbly\Shuttle\Body;

use JsonSerializable;
use Nimbly\Shuttle\EncodingException;

/**
 * Formats an associative array as a JSON encoded string.
 *
 * Sets the Content-Type as "application/json" by default.
 */
class JsonBody extends BufferBody
{
	/**
	 * @param JsonSerializable|array $data
	 * @param string $content_type Defaults to "application/json"
	 */
	public function __construct(JsonSerializable|array $data, string $content_type = "application/json")
	{
		$buffer = \json_encode($data, JSON_UNESCAPED_SLASHES);

		if( $buffer === false ){
			throw new EncodingException("Failed to encode body as JSON.");
		}

		$this->buffer = $buffer;
		$this->content_type = $content_type;
	}
}