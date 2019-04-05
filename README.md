# Shuttle
[![Latest Stable Version](https://img.shields.io/packagist/v/nimbly/Shuttle.svg?style=flat-square)](https://packagist.org/packages/nimbly/Shuttle)
[![Build Status](https://img.shields.io/travis/nimbly/Shuttle.svg?style=flat-square)](https://travis-ci.org/nimbly/Shuttle)
[![Code Coverage](https://img.shields.io/coveralls/github/nimbly/Shuttle.svg?style=flat-square)](https://coveralls.io/github/nimbly/Shuttle)
[![License](https://img.shields.io/github/license/nimbly/Shuttle.svg?style=flat-square)](https://packagist.org/packages/nimbly/Shuttle)


A simple PSR-18 HTTP client library.

## Installation
```bash

composer require nimbly/shuttle

```

## Features
* Responses create php://temp response body stream and swap to disk when necessary.
* cURL (default) and Stream Context handlers supported.
* Middleware support out of the box.
* Easy body transformations when creating requests with JsonBody, FormBody, and XmlBody helper classes.

## Not features
* Asynchronous calls.

## Making requests: The easy way

The quickest and easiest way to begin making requests in Shuttle is to use the HTTP method name:

```php

use Shuttle\Shuttle;

$shuttle = new Shuttle;

$response = $shuttle->get("https://www.google.com");
$response = $shuttle->post("https://example.com/search", "Form data"));

```

Shuttle has built-in methods to support the major HTTP verbs: get, post, put, patch, delete, head, and options. However, you can make **any** HTTP verb request using the **request** method directly.

```php

$response = $shuttle->request("connect", "https://api.example.com/v1/books");

```

## Handling responses

Responses in Shuttle implement PSR-7 ResponseInterface and as such are streamable resources.

```php

$response = $shuttle->get("https://api.example.com/v1/books");

echo $response->getStatusCode(); // 200
echo $response->getReasonPhrase(); // OK
echo $response->isSuccessful(); // true

$body = $response->getBody()->getContents();

```


## Handling failed requests

Shuttle will throw a ```RequestException``` by default if the request failed. This includes things like host name not found, connection timeouts, etc.

Responses with HTTP 4xx or 5xx status codes *will not* throw an exception and must be handled properly within your business logic.

## Making requests: The PSR-7 way

If code reusability and portability is your thing, future proof your code by making requests the PSR-7 way. Remember, PSR-7 stipulates that Request and Response messages be immutable.

```php

use Capsule\Request;
use Capsule\Uri;
use Shuttle\Shuttle;

// Build Request message.
$request = new Request;
$request = $request
    ->withMethod("get")
    ->withUri(new Uri("https://www.google.com"))
    ->withHeader("Accept-Language", "en_US");

// Send the Request.
$shuttle = new Shuttle;
$response = $shuttle->sendRequest($request);

```

## Shuttle client options

* ```handler``` Pass in the HTTP handler instance to be used for all requests. Defaults to ```CurlHandler```. See **Handlers** section for more information.
* ```base_url``` The base URL to prepend to all requests.
* ```http_version``` The default HTTP protocol version to use. Defaults to **1.1**.
* ```headers``` An array of key & value pairs to pass in with each request.
* ```middleware``` An array of middleware instances to be applied to each request and response. See **Middleware** section for more information.
* ```debug``` Enable or disable debug mode. Debug mode prints handler specific debug messages to STDOUT. Defaults to false.

## Request bodies
An easy way to submit data with your request is to use the ```\Shuttle\Body\*``` helper classes. These classes will automatically
transform the data, convert to a **BufferStream**, and set a default **Content-Type** header on the request.

The request bodies support are:

* ``JsonBody`` Converts an associative array into JSON, sets ```Content-Type``` header to ```application/json```.
* ``FormBody`` Converts an associative array into a query string, sets ```Content-Type``` header to ```application/x-www-form-urlencoded```.
* ``XmlBody`` Does no conversion of data, sets ```Content-Type``` header to ```application/xml```.

To submit a JSON payload with a request:

```php

use Shuttle\Body\JsonBody;

$book = [
    "title" => "Breakfast Of Champions",
    "author" => "Kurt Vonnegut",
];

$shuttle->post("https://api.example.com/v1/books", new JsonBody($book));

```

