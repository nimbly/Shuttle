<?php

namespace Shuttle\Tests;

use Capsule\Stream\BufferStream;
use PHPUnit\Framework\TestCase;
use Shuttle\Body\FileUploadBody;
use Shuttle\Body\FormBody;
use Shuttle\Body\JsonBody;
use Shuttle\Body\MultipartFormBody;

/**
 * @covers Shuttle\Body\MultipartFormBody
 * @covers Shuttle\Body\FormBody
 * @covers Shuttle\Body\FileUploadBody
 */
class MultipartFormBodyTest extends TestCase
{
    public function test_multiple_parts()
    {
        $multiPartFormBody = new MultipartFormBody([
            'form' => new FormBody([
                'email' => 'user@example.com',
                'name' => 'Example User',
            ]),

            'file' => new FileUploadBody(
                new BufferStream("Capsule!"),
                'plain.txt',
                'text/plain'
            )
        ]);

        $boundary = "--" . $multiPartFormBody->getBoundary();

        $this->assertEquals(
            "\r\n{$boundary}\r\nContent-Disposition: form-data; name=\"email\"\r\n\r\nuser@example.com\r\n{$boundary}\r\nContent-Disposition: form-data; name=\"name\"\r\n\r\nExample User\r\n{$boundary}\r\nContent-Disposition: form-data; name=\"file\"; filename=\"plain.txt\"\r\nContent-Type: text/plain\r\nContent-Length: 8\r\n\r\nCapsule!\r\n{$boundary}--\r\n",
            $multiPartFormBody->getContents()
        );
    }

    public function test_multiple_parts_without_key_throws_exception()
    {
        $this->expectException(\Exception::class);
        
        $multiPartFormBody = new MultipartFormBody([
            'form' => new FormBody([
                'email' => 'user@example.com',
                'name' => 'Example User',
            ]),

            new FileUploadBody(
                new BufferStream("Capsule!"),
                'plain.txt',
                'text/plain'
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