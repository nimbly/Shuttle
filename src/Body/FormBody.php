<?php

namespace Nimbly\Shuttle\Body;

/**
 * @package Shuttle\Body
 *
 * Format a key => value pair array into a Form Urlencoded string.
 *
 * Sets Content-Type as "application/x-www-form-urlencoded" by default.
 *
 */
class FormBody extends BufferBody
{
	/**
	 * @param array<string,mixed> $data
	 * @param string $content_type
	 */
	public function __construct(array $data, string $content_type = "application/x-www-form-urlencoded")
	{
		parent::__construct(
			\http_build_query($data, "n", "&", PHP_QUERY_RFC1738),
			$content_type
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getMultiPart(string $boundary, ?string $name = null): string
	{
		// Convert the form data back into an array
		\parse_str($this->buffer ?? "", $formFields);

		$multiPart = "";

		foreach( $formFields as $name => $value ){
			$multiPart .= "\r\n--{$boundary}\r\n";
			$multiPart .= "Content-Disposition: form-data; name=\"" . $name . "\"" . "\r\n\r\n";
			$multiPart .= $value;
		}

		return $multiPart;
	}
}