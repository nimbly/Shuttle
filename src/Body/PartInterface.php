<?php

namespace Nimbly\Shuttle\Body;

interface PartInterface
{
	/**
	 * Get the content disposition for multi part.
	 *
	 * @param string $boundary
	 * @param string|null $name
	 * @return string
	 */
	public function getMultiPart(string $boundary, ?string $name = null): string;
}