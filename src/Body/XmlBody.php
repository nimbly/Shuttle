<?php

namespace Nimbly\Shuttle\Body;

/**
 * Passes raw XML data through.
 *
 * Sets the Content-Type as "application/xml" by default.
 */
class XmlBody extends BufferBody
{
	/**
	 * @inheritDoc
	 */
	protected string $content_type = "application/xml";

	/**
	 * @param string $data
	 * @param string|null $content_type
	 */
	public function __construct(string $data = "", ?string $content_type = null)
	{
		$this->buffer = $data;

		if( $content_type ){
			$this->content_type = $content_type;
		}
	}
}