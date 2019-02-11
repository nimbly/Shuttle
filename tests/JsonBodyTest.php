<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Shuttle\Body\JsonBody;

class JsonBodyTest extends TestCase
{
    public function test_default_content_type()
    {
        $jsonBody = new JsonBody([]);

        $this->assertEquals("application/json", $jsonBody->getContentType());
    }

    public function test_form_body_transformation()
    {
        $jsonBody = new JsonBody(
            [
                "name" => "John Doe",
                "email" => "jdoe@example.com",
            ]
        );

        $this->assertEquals('{"name":"John Doe","email":"jdoe@example.com"}', $jsonBody->getContents());
    }
}