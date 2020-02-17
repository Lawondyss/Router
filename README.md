# Router

PHP library to create REST API.

## Requirements

Strictly requires PHP 7.4


## Install

Via Composer: `composer require lawondyss/router`


## Usage

Simply instantiate. 

```php
use Lawondyss\Sandy\Router\Router;

require_once __DIR__ . '/vendor/autoload.php';

$router = new Router;
```

No static. Why not?


### Defining routes

Add routes for HTTP method(s) and URL path (as mask). Callback can processing request and editing response.

```php
// predefined HTTP method
$router->get('/', function (Request $request, Response $response) {
  $response->body = 'Hello world';
});

// predefined HTTP method
$router->post('/foo/bar', function(Request $request, Response $response) {
  $response->code = Response::S201_CREATED;
});

// own definition of valid HTTP methods (PUT and PATCH)
$router->add(Router::PUT | Router::PATCH, '/foo/bar', function (Request $request, Response $response) {
  $response->code = Response::S204_NO_CONTENT;
  $response->addHeader('X-Lipsum-Message', 'Lorem ipsum dolor sit amet');
});
```


### Parameters in mask

For catching segments of URL use `{` and `}`.

Optional segments of URL is in between `[` and `]`.

Definition of regex validation for catching segment is after `:`.

All catching segments is in `Request::$params`. 

```php
$router->get('/foo/{bar:\d+}[/{baz:[a-z]+}]', function(Request $request, Response $response) {
  $response->contentType = 'application/json';
  $response->body = Json::encode($request->params);
});
```


## Motivation

Mainly for my educational purposes.


## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
