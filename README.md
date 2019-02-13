# Shuttle
[![Latest Stable Version](https://img.shields.io/packagist/v/nimbly/Shuttle.svg?style=flat-square)](https://packagist.org/packages/nimbly/Shuttle)
[![Build Status](https://img.shields.io/travis/nimbly/Shuttle.svg?style=flat-square)](https://travis-ci.org/nimbly/Shuttle)
[![License](https://img.shields.io/github/license/nimbly/Shuttle.svg?style=flat-square)](https://packagist.org/packages/nimbly/Shuttle)


A simple PSR-7 HTTP library.

## Installation
```bash

composer require nimbly/shuttle

```

## Features
* PSR-7 implementation of Request and Response objects.
* Responses create php://temp response body stream and swap to disk if necessary.
* Middleware support out of the box.
* Easy body transformations when creating requests with JsonBody, FormBody, and XmlBody helper classes.


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

$body = $response->getBody()->getContents();

```

## Handling failed requests

Shuttle will throw an exception by default if the request failed.

## Making requests: The PSR-7 way

If code reusability and portability is your thing, future proof your code by making requests the PSR-7 way. Remember, PSR-7 stipulates that Request and Response messages be immutable.

```php

use Shuttle\Request;
use Shuttle\Shuttle;
use Shuttle\Uri;

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

## Request bodies
An easy way to submit data with your request is to use the ```\Shuttle\Body\*``` helper classes. These classes will automatically
transform the data, convert to a **BufferStream**, and set a default **Content-Type** header on the request.

To submit a JSON payload with a request:

```php

use Shuttle\Body\JsonBody;

$book = [
    "title" => "Breakfast Of Champions",
    "author" => "Kurt Vonnegut",
];

$shuttle->post("https://api.example.com/v1/books", new JsonBody($book));

```