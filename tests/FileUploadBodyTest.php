<?php

namespace Nimbly\Capsule\Tests;

use Nimbly\Shuttle\Body\FileUploadBody;
use Nimbly\Capsule\Stream\BufferStream;
use PHPUnit\Framework\TestCase;

/**
 * @covers Nimbly\Shuttle\Body\FileUploadBody
 */
class FileUploadBodyTest extends TestCase
{
	public function test_create_instance_from_file_on_disk()
	{
		$fileUploadBody = new FileUploadBody(__DIR__ . "/files/plainText.txt");

		$this->assertEquals(
			"\r\n--BOUNDARY\r\nContent-Disposition: form-data; name=\"file\"; filename=\"plainText.txt\"\r\nContent-Type: text/plain\r\nContent-Length: 8\r\n\r\nShuttle!",
			$fileUploadBody->getMultiPart("BOUNDARY", "file")
		);
	}

	public function test_create_instance_from_file_on_disk_with_filename_override()
	{
		$fileUploadBody = new FileUploadBody(__DIR__ . "/files/plainText.txt", "plain.txt");

		$this->assertEquals(
			"\r\n--BOUNDARY\r\nContent-Disposition: form-data; name=\"file\"; filename=\"plain.txt\"\r\nContent-Type: text/plain\r\nContent-Length: 8\r\n\r\nShuttle!",
			$fileUploadBody->getMultiPart("BOUNDARY", "file")
		);
	}

	public function test_create_instance_from_file_on_disk_with_content_type_override()
	{
		$fileUploadBody = new FileUploadBody(__DIR__ . "/files/plainText.txt", null, "text/html");

		$this->assertEquals(
			"\r\n--BOUNDARY\r\nContent-Disposition: form-data; name=\"file\"; filename=\"plainText.txt\"\r\nContent-Type: text/html\r\nContent-Length: 8\r\n\r\nShuttle!",
			$fileUploadBody->getMultiPart("BOUNDARY", "file")
		);
	}

	public function test_create_instance_from_stream()
	{
		$fileUploadBody = new FileUploadBody(new BufferStream("Shuttle!"));

		$this->assertEquals(
			"\r\n--BOUNDARY\r\nContent-Disposition: form-data; name=\"file\"; filename=\"file\"\r\nContent-Type: text/plain\r\nContent-Length: 8\r\n\r\nShuttle!",
			$fileUploadBody->getMultiPart("BOUNDARY", "file")
		);
	}

	public function test_create_instance_from_stream_with_filename_override()
	{
		$fileUploadBody = new FileUploadBody(new BufferStream("Shuttle!"), "buffer.txt");

		$this->assertEquals(
			"\r\n--BOUNDARY\r\nContent-Disposition: form-data; name=\"file\"; filename=\"buffer.txt\"\r\nContent-Type: text/plain\r\nContent-Length: 8\r\n\r\nShuttle!",
			$fileUploadBody->getMultiPart("BOUNDARY", "file")
		);
	}

	public function test_create_instance_from_stream_with_content_type_override()
	{
		$fileUploadBody = new FileUploadBody(new BufferStream("Shuttle!"), null, "text/html");

		$this->assertEquals(
			"\r\n--BOUNDARY\r\nContent-Disposition: form-data; name=\"file\"; filename=\"file\"\r\nContent-Type: text/html\r\nContent-Length: 8\r\n\r\nShuttle!",
			$fileUploadBody->getMultiPart("BOUNDARY", "file")
		);
	}
}