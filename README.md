# Shuttle
[![Latest Stable Version](https://img.shields.io/packagist/v/nimbly/Shuttle.svg?style=flat-square)](https://packagist.org/packages/nimbly/Shuttle)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/nimbly/shuttle/php.yml?style=flat-square)](https://github.com/nimbly/Shuttle/actions/workflows/php.yml)
[![Codecov branch](https://img.shields.io/codecov/c/github/nimbly/shuttle/master?style=flat-square)](https://app.codecov.io/github/nimbly/Shuttle)
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
* Easy body transformations when creating requests with JsonBody and FormBody helper classes.

## Not features
* Asynchronous calls.

## A note on PSR-7 and PSR-17

Shuttle makes use of PSR-7 HTTP Message and will default to using `nimbly/capsule`. You can override this by providing your perferred choice in PSR-7 implementations by passing PSR-17 HTTP Factories instances into the constructor of `Shuttle`.

```php
$http_factory = new GuzzleHttp\Psr7\HttpFactory;

$shuttle = new Shuttle(
	requestFactory: $http_factory,
	responseFactory: $http_factory,
	streamFactory: $http_factory,
	uriFactory: $http_factory,
);
```

## Making requests: The easy way

The quickest and easiest way to begin making requests in Shuttle is to use the HTTP method name:

```php
use Nimbly\Shuttle\Shuttle;

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

$body = $response->getBody()->getContents(); // {"title": "Do Androids Dream of Electric Sheep?", "author": "Philip K. Dick"}
```

## Handling failed requests

Shuttle will throw a `RequestException` by default if the request failed. This includes things like host name not found, connection timeouts, etc.

Responses with HTTP 4xx or 5xx status codes *will not* throw an exception and must be handled properly within your business logic.

## Making requests: The PSR-7 way

If code reusability and portability is your thing, future proof your code by making requests the PSR-7 way. Remember, PSR-7 stipulates that Request and Response messages be immutable.

```php
// Build Request message with your favorite PSR-7 library.
$request = new Request("get", "https://www.example.com");

// Send the Request.
$shuttle = new Shuttle;
$response = $shuttle->sendRequest($request);
```

Using the `sendRequest()` method *does not* apply any `base_url` or default `headers` passed into the Shuttle constructor. However, the request is still passed through the middleware chain.

## Request bodies
An easy way to submit data with your request is to use the `Nimbly\Shuttle\Body\*` helper classes. These classes will automatically transform the data, convert to a **BufferStream**, and set a default **Content-Type** header on the request.

The request bodies supported are:

* `JsonBody` Converts an associative array or instance of `JsonSerializable` into JSON and sets the `Content-Type` header to `application/json`.
* `FormBody` Converts an associative array into a query string, sets `Content-Type` header to `application/x-www-form-urlencoded`.

To submit a JSON payload with a request:

```php
use Nimbly\Shuttle\Body\JsonBody;

$book = [
    "title" => "Breakfast Of Champions",
    "author" => "Kurt Vonnegut",
];

$shuttle->post("https://api.example.com/v1/books", new JsonBody($book));
```

## Middleware

Shuttle supports dual (aka double) pass middleware by implementing `MiddlewareInterface`. The request and response instance are both available to the middleware and can be manipulated to your specific needs.

```php
class AuthMiddleware implements MiddlewareInterface
{
	public function __construct(
		private string $api_key)
	{
	}

	public function process(RequestInterface $request, callable $next): ResponseInterface
	{
		// Add the Authorization header with every outgoing request.
		$request = $request->withAddedHeader("Authorization", "Bearer " . $this->api_key);

		// Pass request object to next middleware layer.
		$response = $next($request);

		// Return response back with custom header added.
		return $response->withAddedHeader("X-Custom-Header", "Foo");
	}
}
```

You may add as many middleware layers as you need and pass them to the Shuttle constructor. The middleware are executed in the order given.

```php
$shuttle = new Shuttle(
	middleware: [
		new AuthMiddleware(\getenv("API_KEY")),
		new FooMiddleware,
		new BazMiddleware,
	]
);
```
