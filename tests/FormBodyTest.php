<?php

namespace Nimbly\Shuttle\Tests;

use PHPUnit\Framework\TestCase;
use Nimbly\Shuttle\Body\FormBody;

/**
 * @covers Nimbly\Shuttle\Body\FormBody
 * @covers Nimbly\Shuttle\Body\BufferBody
 */
class FormBodyTest extends TestCase
{
	public function test_default_content_type()
	{
		$formBody = new FormBody([]);

		$this->assertEquals("application/x-www-form-urlencoded", $formBody->getContentType());
	}

	public function test_override_content_type()
	{
		$formBody = new FormBody([], "multipart/form-data");

		$this->assertEquals("multipart/form-data", $formBody->getContentType());
	}

	public function test_form_body_transformation()
	{
		$formBody = new FormBody(
			[
				"name" => "John Doe",
				"email" => "jdoe@example.com",
			]
		);

		$this->assertEquals("name=John+Doe&email=jdoe%40example.com", $formBody->getContents());
	}
}