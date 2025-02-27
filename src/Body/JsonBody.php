<?php

namespace Nimbly\Shuttle\Body;

use JsonSerializable;
use Nimbly\Shuttle\EncodingException;

/**
 * Formats an associative array or any JsonSerializable as a JSON encoded string and sets the Content-Type to "application/json" by default.
 */
class JsonBody extends BufferBody
{
	/**
	 * @param JsonSerializable|array<string,mixed>|array<mixed> $data
	 * @param string $content_type Defaults to "application/json"
	 */
	public function __construct(JsonSerializable|array $data, string $content_type = "application/json")
	{
		$buffer = \json_encode($data, JSON_UNESCAPED_SLASHES);

		if( \json_last_error() !== JSON_ERROR_NONE || $buffer === false ){
			throw new EncodingException("Failed to encode body as JSON.");
		}

		parent::__construct($buffer, $content_type);
	}
}