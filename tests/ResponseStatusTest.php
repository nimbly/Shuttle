<?php

namespace Shuttle\Tests;

use PHPUnit\Framework\TestCase;
use Shuttle\ResponseStatus;

/**
 * @package Shuttle\Tests
 * 
 * @covers Shuttle\ResponseStatus
 */
class ResponseStatusTest extends TestCase
{
    public function test_supported_response_code_returns_phrase()
    {
        $this->assertNotNull(ResponseStatus::getPhrase(201));
    }

    public function test_unsupported_response_code_returns_null()
    {
        $this->assertNull(ResponseStatus::getPhrase(420));
    }
}