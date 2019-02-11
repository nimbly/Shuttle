<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Shuttle\Uri;

class UriTest extends TestCase
{
    public function test_missing_protocol_is_rejected()
    {
        $this->expectException(\Exception::class);
        $uri = new Uri("foo.com");
    }

    public function test_invalid_hostname_is_rejected()
    {
        $this->expectException(\Exception::class);
        $uri = new Uri("https://username:password@?q=books");
    }

    public function test_constructor_parses_all_uri_parts()
    {
        $url = "https://username:password@www.example.com:443/path/to/some/resource?q=foo&s=some+search+text&n=John%20Doe#fragment-1";
        $uri = new Uri($url);

        $this->assertEquals("https", $uri->getScheme());
        $this->assertEquals("username:password", $uri->getUserInfo());
        $this->assertEquals("www.example.com", $uri->getHost());
        $this->assertEquals(443, $uri->getPort());
        $this->assertEquals("/path/to/some/resource", $uri->getPath());
        $this->assertEquals("q=foo&s=some+search+text&n=John%20Doe", $uri->getQuery());
        $this->assertEquals("fragment-1", $uri->getFragment());
        $this->assertEquals("username:password@www.example.com:443", $uri->getAuthority());
    }

    public function test_uri_cast_as_string()
    {
        $url = "https://username:password@www.example.com:443/path/to/some/resource?q=foo&s=some+search+text&n=John%20Doe#fragment-1";
        $uri = new Uri($url);
        $this->assertEquals($url, (string) $uri);
    }

    public function test_uri_derives_https_port_number_if_not_provided()
    {
        $uri = new Uri("https://www.example.com");
        $this->assertEquals(443, $uri->getPort());
    }

    public function test_uri_derives_http_port_number_if_not_provided()
    {
        $uri = new Uri("http://www.example.com");
        $this->assertEquals(80, $uri->getPort());
    }

    public function test_with_scheme_saves_data()
    {
        $uri = (new Uri)->withScheme("https");
        $this->assertEquals("https", $uri->getScheme());
    }

    public function test_with_scheme_is_immutable()
    {
        $uri = new Uri;
        $newUri = $uri->withScheme("https");

        $this->assertEmpty($uri->getScheme());
        $this->assertNotEquals($uri, $newUri);
    }

    public function test_with_userinfo_saves_data()
    {
        $uri = (new Uri)->withUserInfo("username", "password");
        $this->assertEquals("username:password", $uri->getUserInfo());
    }

    public function test_with_userinfo_is_immutable()
    {
        $uri = new Uri;
        $newUri = $uri->withUserInfo("username", "password");

        $this->assertEmpty($uri->getScheme());
        $this->assertNotEquals($uri, $newUri);
    }
    
    public function test_with_host_saves_data()
    {
        $uri = (new Uri)->withHost("www.example.com");
        $this->assertEquals("www.example.com", $uri->getHost());
    }

    public function test_with_host_is_immutable()
    {
        $uri = new Uri;
        $newUri = $uri->withHost("www.example.com");

        $this->assertEmpty($uri->getHost());
        $this->assertNotEquals($uri, $newUri);
    }

    public function test_with_port_saves_data()
    {
        $uri = (new Uri)->withPort(443);
        $this->assertEquals(443, $uri->getPort());
    }

    public function test_with_port_is_immutable()
    {
        $uri = new Uri;
        $newUri = $uri->withPort(443);

        $this->assertEmpty($uri->getPort());
        $this->assertNotEquals($uri, $newUri);
    }

    public function test_with_path_saves_data()
    {
        $uri = (new Uri)->withPath("/some/path/to/resource");
        $this->assertEquals("/some/path/to/resource", $uri->getPath());
    }

    public function test_with_path_is_immutable()
    {
        $uri = new Uri;
        $newUri = $uri->withPath("/some/path/to/resource");

        $this->assertEmpty($uri->getPath());
        $this->assertNotEquals($uri, $newUri);
    }

    public function test_with_query_saves_data()
    {
        $uri = (new Uri)->withQuery("q=foo&s=some search text");
        $this->assertEquals("q=foo&s=some search text", $uri->getQuery());
    }

    public function test_with_query_is_immutable()
    {
        $uri = new Uri;
        $newUri = $uri->withQuery("q=foo&s=some search text");

        $this->assertEmpty($uri->getQuery());
        $this->assertNotEquals($uri, $newUri);
    }

    public function test_with_fragment_saves_data()
    {
        $uri = (new Uri)->withFragment("Chapter1");
        $this->assertEquals("Chapter1", $uri->getFragment());
    }

    public function test_with_fragment_is_immutable()
    {
        $uri = new Uri;
        $newUri = $uri->withFragment("Chapter1");

        $this->assertEmpty($uri->getFragment());
        $this->assertNotEquals($uri, $newUri);
    }
}