![Travis CI](https://travis-ci.org/nimbly/Shuttle.svg?branch=master)

## Shuttle
A simple PSR-7 HTTP library.

### Installation
```bash
composer require nimbly/shuttle
```

### Features
* PSR-7 implementation of Request and Response objects.
* Responses create php://temp response body stream and swap to disk if necessary.
* Middleware support.
* Easy body transformations when creating requests with JsonBody, FormBody, and XmlBody helper classes.

### Making requests (the easy way)

The quickest and easiest way to begin making requests in Shuttle is to use the HTTP method as the method-name.

```php
$shuttle = new \Shuttle\Shuttle;

$response = $shuttle->get("https://www.google.com");
$response = $shuttle->post("https://example.com/search", new \Shuttle\Body\FormBody(["q" => "my search query"]));
```

Shuttle has built-in methods to support the major HTTP verbs: get, post, put, patch, delete, head, and options. However, you can make **any** HTTP verb request using the **request** method directly.

```php

$response = $shuttle->request("connect", "https://api.example.com/books");

```

### Making requests (the PSR-7 compliant way)

If code reusability and portability is your thing, future proof your code by making requests the PSR-7 way. Remember, PSR-7 stipulates that Request and Response messages be immutable.

```php

// Build Request message.
$request = new \Shuttle\Request;
$request = $request
    ->withMethod("get")
    ->withUri(new \Shuttle\Uri("https://www.google.com"))
    ->withHeader("Accept-Language", "en_US");

// Send the Request.
$shuttle = new \Shuttle\Shuttle;
$response = $shuttle->sendRequest($request);

```