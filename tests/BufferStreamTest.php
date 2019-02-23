<?php

namespace Shuttle\Tests;

use PHPUnit\Framework\TestCase;
use Shuttle\Stream\BufferStream;

/**
 * @covers Shuttle\Stream\BufferStream
 */
class BufferStreamTest extends TestCase
{
    public function test_contructor_applies_data()
    {
        $bufferStream = new BufferStream("Shuttle!");
        $this->assertEquals("Shuttle!", $bufferStream->getContents());
    }

    public function test_casting_to_string_returns_contents()
    {
        $bufferStream = new BufferStream("Shuttle!");
        $this->assertEquals("Shuttle!", (string) $bufferStream);
    }

    public function test_close_resets_buffer_contents()
    {
        $bufferStream = new BufferStream("Shuttle!");
        $bufferStream->close();

        $this->assertEquals("", $bufferStream->getContents());
    }

    public function test_detach_resets_buffer_contents()
    {
        $bufferStream = new BufferStream("Shuttle!");
        $bufferStream->close();

        $this->assertEquals("", $bufferStream->getContents());
    }

    public function test_getsize_returns_string_length_of_buffer()
    {
        $bufferStream = new BufferStream("Shuttle!");

        $this->assertEquals(8, $bufferStream->getSize());
    }

    public function test_tell_of_bufferstream_throws_exception()
    {
        $this->expectException(\Exception::class);
        
        $bufferStream = new BufferStream("Shuttle!");
        $bufferStream->tell();
    }

    public function test_eof_when_buffer_is_empty()
    {
        $bufferStream = new BufferStream;
        $this->assertTrue($bufferStream->eof());
    }

    public function test_is_not_seekable()
    {
        $bufferStream = new BufferStream;
        $this->assertTrue(!$bufferStream->isSeekable());
    }

    public function test_seek_throws_exception()
    {
        $this->expectException(\Exception::class);
        
        $bufferStream = new BufferStream("Shuttle!");
        $bufferStream->seek(0);
    }

    public function test_rewind_throws_exception()
    {
        $bufferStream = new BufferStream("Shuttle!");

        $this->expectException(\Exception::class);
        $bufferStream->rewind();
    }

    public function test_is_writeable()
    {
        $bufferStream = new BufferStream;
        
        $this->assertTrue($bufferStream->isWritable());
    }

    public function test_write_returns_bytes_written()
    {
        $bufferStream = new BufferStream;
        $bytesWritten = $bufferStream->write("Shuttle!");

        $this->assertEquals(8, $bytesWritten);
    }

    public function test_write_appends_data()
    {
        $bufferStream = new BufferStream("I love");
        $bufferStream->write(" Shuttle!");

        $this->assertEquals("I love Shuttle!", $bufferStream->getContents());
    }

    public function test_is_readable()
    {
        $bufferStream = new BufferStream;

        $this->assertTrue($bufferStream->isReadable());
    }

    public function test_reading_more_bytes_than_available()
    {
        $bufferStream = new BufferStream("Shuttle!");
        $data = $bufferStream->read(25);

        $this->assertEquals("Shuttle!", $data);
    }

    public function test_reading_fewer_bytes_than_available()
    {
        $bufferStream = new BufferStream("Shuttle!");
        $data = $bufferStream->read(2);

        $this->assertEquals("Sh", $data);
    }

    public function test_reading_bytes_removes_from_stream()
    {
        $bufferStream = new BufferStream("Shuttle!");
        $bufferStream->read(2);
        $data = $bufferStream->read(6);

        $this->assertEquals("uttle!", $data);
    }

    public function test_get_contents_returns_entire_buffer()
    {
        $bufferStream = new BufferStream("Shuttle!");
        $data = $bufferStream->getContents();
        $this->assertEquals("Shuttle!", $data);
    }

    public function test_get_contents_empties_buffer()
    {
        $bufferStream = new BufferStream("Shuttle!");
        $bufferStream->getContents();

        $this->assertEquals("", $bufferStream->getContents());
        $this->assertTrue($bufferStream->eof());
    }

    public function test_get_meta_data_returns_nothing()
    {
        $bufferStream = new BufferStream("Shuttle!");
        $this->assertEquals(null, $bufferStream->getMetadata());
    }
}