<?php

namespace Nimbly\Shuttle\Tests;

use Nimbly\Capsule\Stream\BufferStream;
use PHPUnit\Framework\TestCase;
use Nimbly\Shuttle\Body\FileUploadBody;
use Nimbly\Shuttle\Body\FormBody;
use Nimbly\Shuttle\Body\JsonBody;
use Nimbly\Shuttle\Body\MultipartFormBody;

/**
 * @covers Nimbly\Shuttle\Body\MultipartFormBody
 * @covers Nimbly\Shuttle\Body\FormBody
 * @covers Nimbly\Shuttle\Body\FileUploadBody
 * @covers Nimbly\Shuttle\Body\BufferBody
 */
class MultipartFormBodyTest extends TestCase
{
	public function test_multiple_parts()
	{
		$multiPartFormBody = new MultipartFormBody([
			"form" => new FormBody([
				"email" => "user@example.com",
				"name" => "Example User",
			]),

			"file" => new FileUploadBody(
				new BufferStream("Shuttle!"),
				"plain.txt",
				"text/plain"
			)
		]);

		$boundary = "--" . $multiPartFormBody->getBoundary();

		$this->assertEquals(
			"\r\n{$boundary}\r\nContent-Disposition: form-data; name=\"email\"\r\n\r\nuser@example.com\r\n{$boundary}\r\nContent-Disposition: form-data; name=\"name\"\r\n\r\nExample User\r\n{$boundary}\r\nContent-Disposition: form-data; name=\"file\"; filename=\"plain.txt\"\r\nContent-Type: text/plain\r\nContent-Length: 8\r\n\r\nShuttle!\r\n{$boundary}--\r\n",
			$multiPartFormBody->getContents()
		);
	}

	public function test_multiple_parts_without_key_throws_exception()
	{
		$this->expectException(\Exception::class);

		$multiPartFormBody = new MultipartFormBody([
			"form" => new FormBody([
				"email" => "user@example.com",
				"name" => "Example User",
			]),

			new FileUploadBody(
				new BufferStream("Shuttle!"),
				"plain.txt",
				"text/plain"
			)
		]);
	}

	public function test_multipart_content_type()
	{
		$multiPartFormBody = new MultipartFormBody([]);

		$this->assertEquals(
			"multipart/form-data;boundary=" . $multiPartFormBody->getBoundary(),
			$multiPartFormBody->getContentType()
		);
	}
}