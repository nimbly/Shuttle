<?php

namespace Shuttle\Tests;

use PHPUnit\Framework\TestCase;
use Shuttle\Body\BufferBody;

/**
 * @covers Shuttle\Body\BufferBody
 * @covers Capsule\Stream\BufferStream
 */
class BufferBodyTest extends TestCase
{
    public function test_setting_body_in_buffer_body_constructor()
    {
        $bufferBody = new BufferBody("OK", "text/plain");
        $this->assertEquals("OK", $bufferBody->getContents());
    }

    public function test_setting_content_type_in_buffer_body_constructor()
    {
        $bufferBody = new BufferBody("OK", "text/plain");
        $this->assertEquals("text/plain", $bufferBody->getContentType());
    }

    public function test_get_multipart()
    {
        $bufferBody = new BufferBody("OK");

        $this->assertEquals(
            "\r\n--Capsule\r\nContent-Disposition: form-data; name=\"test\"\r\nContent-Type: text/plain\r\n\r\nOK",
            $bufferBody->getMultiPart("Capsule", "test")
        );
    }
}