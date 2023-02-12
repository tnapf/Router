# Tnapf/Router

Totally Not Another PHP Framework's Route Component

# Table of Contents

- [Installation](#Installation)
- [Creating routes](#routing)
- [Route Patterns](#route-patterns)
- [Controllers](#controllers)
- [Using Template Engines](#template-engine-integration)
- [Responses](#responding-to-requests)

# Installation

```
composer require tnapf/router
```

# Basic Usage

```php
<?php

require_once "vendor/autoload.php";

use Tnapf\Router\Router;
use HttpSoft\Response\TextResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

Router::get("/user/{username}", function (ServerRequestInterface $req, ResponseInterface $res, stdClass $args): ResponseInterface {
    return new TextResponse("Viewing {$args->username}'s Profile");
})->setParameter("username", "[a-zA-Z_]+");

Router::run();
```


# Routing

You can manually create a route and then store it with the addRoute method

```php
$route = new Route("pattern", function() { /* ... */ }, Methods::GET);

Router::addRoute($route);
```

If you want to the same controller to be used for multiple methods you can do the following...

```php
use Tnapf\Router\Enums\Methods;

$route = new Route("pattern", function() { /* ... */ }, Methods::GET, Methods::POST, Methods::HEAD, ...);

Router::addRoute($route);
```

## Routing Shorthands

Shorthands for single request methods are provided

```php
Router::get('pattern', function() { /* ... */ });
Router::post('pattern', function() { /* ... */ });
Router::put('pattern', function() { /* ... */ });
Router::delete('pattern', function() { /* ... */ });
Router::options('pattern', function() { /* ... */ });
Router::patch('pattern', function() { /* ... */ });
Router::head('pattern', function() { /* ... */ });
```

You can use this shorthand for a route that can be accessed using any method:

```php
$router->all('pattern', function() { /* ... */ });
```

# Route Patterns

Route Patterns can be static or dynamic:

- __Static Route Patterns__ contain no dynamic parts and must match exactly against the `path` part of the current URL.
- __Dynamic Route Patterns__ contain dynamic parts that can vary per request. The varying parts are named __subpatterns__ and are defined using either Perl-compatible regular expressions (PCRE) or by using __placeholders__

## Static Route Patterns

A static route pattern is a regular string representing a URI. It will be compared directly against the `path` part of the current URL.

Examples:

-  `/about`
-  `/contact`

Usage Examples:

```php
Router::get('/about', function(ServerRequestInterface $req, ResponseInterface $res) {
    $res->getBody()->write("Hello World");
    return $res;
});
```

## Dynamic Placeholder-based Route Patterns

```php
$route = Router::get("/profile/{username}", ProfileController::class, Methods::GET);
```

This type of Route Pattern is the same as __Dynamic PCRE-based Route Patterns__, but with one difference: they don't use regexes to do the pattern matching but they use the easy **placeholders** instead. Placeholders are strings surrounded by curly braces, e.g. `{name}`.
Examples:

- `/movies/{id}`
- `/profile/{username}`

Placeholders are easier to use than PRCEs, but offer you less control as they internally get translated to a PRCE that matches any character (`.*`).

```php
Router::get('/movies/{movieId}/photos/{photoId}', function(ServerRequestInterface $req, ResponseInterface $res, stdClass $args) {
    $res->getBody()->write('Movie #'.$args->movieId.', photo #'.$args->photoId);
    return $res;
});
```

## Dynamic PCRE-based Route Patterns

This type of Route Pattern contains dynamic parts which can vary per request. The varying parts are named __subpatterns__ and are defined using regular expressions.

Commonly used PCRE-based subpatterns within Dynamic Route Patterns are:

- `\d+` = One or more digits (0-9)
- `\w+` = One or more word characters (a-z 0-9 _)
- `[a-z0-9_-]+` = One or more word characters (a-z 0-9 _) and the dash (-)
- `.*` = Any character (including `/`), zero or more
- `[^/]+` = Any character but `/`, one or more

Note: The [PHP PCRE Cheat Sheet](https://courses.cs.washington.edu/courses/cse154/15sp/cheat-sheets/php-regex-cheat-sheet.pdf) might come in handy.

The __subpatterns__ defined in Dynamic PCRE-based Route Patterns are passed into the route's controller like __dynamic placeholders__.

```php
Router::get('/hello/{name}', function(ServerRequestInterface $req, ResponseInterface $res, stdClass $args) {
    $res->getBody()->write('Hello '.htmlentities($args->name));
    return $res;
})->setParameter("name", "\w+");
```

Note: The leading `/` at the very beginning of a route pattern is not mandatory, but is recommended.

When multiple subpatterns are defined, the resulting __route handling parameters__ are passed into the route handling function in the order they are defined:

```php
Router::get('/movies/{movieId}/photos/{photoId}', function(ServerRequestInterface $req, ServerResponseInterface $res, stdClass $args) {
    $res->getBody()->write('Movie #'.$args->movieId.', photo #'.$args->photoId);
    return $res;
})->setParameter("movieId", "\d+")->setParameter("photoId", "\d+");
```

# Controllers

When defining a route you can either pass an anonymous function or an array that contains a class along with a static method to invoke. Additionally, your controller must return an implementation of the PSR7 Response Interface

## Anonymous Function Controller

```php
Router::get("/home", function (ServerRequestInterface $req, ResponseInterface $res) {
    $res->getBody()->write("Welcome home!");
    return $res;
});
```

## Class Controller

Create class that extends `Tnapf\Router\Routing\Controller`

```php
class HomeController extends Controller {
    public static function handle(ServerRequestInterface $req, ResponseInterface $res, stdClass $args): ResponseInterface
    {
        $res->getBody()->write("Welcome home!");
        return $res;
    }
}
```

Then insert the class string in for the controller argument instead of the anonymous function

```php
Router::get("/home", HomeController::class);
```

# Template Engine Integration

You can use [CommandString/Env](https://github.com/commandstring/env) to store your template engine object in a singleton. Then you can easily get it without trying to pass it around to your controller

```php
use CommandString\Env\Env;

$env = new Env;
$env->twig = new Environment(new \Twig\Loader\FilesystemLoader("/path/to/views"));

// ...

Router::get("/home", function (ServerRequestInterface $req, ResponseInterface $res): ResponseInterface {
    return new HtmlResponse($env->get("twig")->render("home.html"));
});
```

# Responding to requests
All controllers **MUST** return an implementation of `\Psr\Http\Message\ResponseInterface`. You can use the premade response object passed into the controller *or* instantiate your own. I recommend taking a look at [HttpSoft/Response](https://httpsoft.org/docs/response/v1/#usage) for prebuilt response types. This is also included with the route as it's used for the dev mode
```php
$response = new HttpSoft\Response\HtmlResponse('<p>HTML</p>');
$response = new HttpSoft\Response\JsonResponse(['key' => 'value']);
$response = new HttpSoft\Response\JsonResponse("{key: 'value'}");
$response = new HttpSoft\Response\TextResponse('Text');
$response = new HttpSoft\Response\XmlResponse('<xmltag>XML</xmltag>');
$response = new HttpSoft\Response\RedirectResponse('https/example.com');
$response = new HttpSoft\Response\EmptyResponse();
```