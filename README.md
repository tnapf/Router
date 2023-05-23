# Tnapf/Router

Totally Not Another PHP Framework's Route Component

# Table of Contents

- [Installation](#installation)
- [Routing](#routing)
  - [Routing Shorthands](#routing-shorthands)
- [Route Patterns](#route-patterns)
  - [Static Route Patterns](#static-route-patterns)
  - [Dynamic Placeholder-based Route Patterns](#dynamic-placeholder-based-route-patterns)
  - [Dynamic PCRE-based Route Patterns](#dynamic-pcre-based-route-patterns)
- [Controllers](#controllers)
- [Responding to requests](#responding-to-requests)
- [Catchable Routes](#catchable-routes)
  - [Catching](#catching)
  - [Specific URI's](#specific-uris)
- [Middleware](#middleware)
- [Postware](#postware)
- [Group routes](#group-routes)
- [Static Arguments](#static-arguments)
- [React/Http Integration](#reacthttp-integration)

# Installation

```
composer require tnapf/router
```

# Routing

You can manually create a route and then store it with the addRoute method

```php
use Tnapf\Router\Router;
use Tnapf\Router\Routing\Methods;
use Tnapf\Router\Routing\RouteRunner;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$router = new Router();
$handler = static function (
    ServerRequestInterface $request,
    ResponseInterface $response,
    RouteRunner $route
) {
    $response->getBody()->write("Hello World");
    return $response;
};

$route = Route::new(uri: "uri", controller: $handler, methods: Methods::GET);
$router->addRoute($route);
```

If you want the same controller to be used for multiple methods you can do the following...

```php
$route = Route::new(uri: "uri", controller: static fn() => new TextResponse(), methods: Methods::GET, Methods::POST); // can also spread Methods::ALL
$router->addRoute($route);
```

## Routing Shorthands

Shorthands for single request methods are provided

```php
$router->get('uri', $handler);
$router->post('uri', $handler);
$router->put('uri', $handler);
$router->delete('uri', $handler);
$router->options('uri', $handler);
$router->patch('uri', $handler);
$router->head('uri', $handler);
```

You can use this shorthand for a route that can be accessed using any method:

```php
$router->all('uri', $handler);
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
$router->get(
    '/about',
    static function (
        ServerRequestInterface $request,
        ResponseInterface $response,
        RouteRunner $route
    ) {
        $response->getBody()->write("About Us");
        return $response;
    }
);
```

## Dynamic Placeholder-based Route Patterns

```php
$router->get(
    "/profile/{username}",
    static function (
        ServerRequestInterface $request,
        ResponseInterface $response,
        RouteRunner $route
    ) {
        $response->getBody()->write("Hello, {$route->getParameter("username")}!");
        return $response;
    }
);
```

This type of Route Pattern is the same as __Dynamic PCRE-based Route Patterns__, but with one difference: they don't use regexes to do the pattern matching but they use the easy **placeholders** instead. Placeholders are strings surrounded by curly braces, e.g. `{name}`.
Examples:

- `/movies/{id}`
- `/profile/{username}`

Placeholders are easier to use than PRCEs, but offer you less control as they internally get translated to a PRCE that matches any character (`.*`).

```php
$router->get(
    '/movies/{movieId}/photos/{photoId}',
    static function (
        ServerRequestInterface $request,
        ResponseInterface $response,
        RouteRunner $route
    ) {
        $response->getBody()
            ->write("Movie #{$route->getParameter('movieId')} | Photo #{$route->getParameter('photoId')}");
        return $response;
    }
);

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
$router->get(
    '/hello/{name}',
    static function (
        ServerRequestInterface $request,
        ResponseInterface $response,
        RouteRunner $route
    ) {
        $response->getBody()->write("Hello {$route->getParameter('name')}");
        return $response;
    }
)->setParameter('name', '[a-zA-Z]+');
```
]
# Controllers

When defining a route you pass an instance of a class that implements `Tnapf\Router\Interfaces\ControllerInterface` or a closure which will get converted into an instance of `Tnapf\Handlers\ClosureRequestHandler` 

```php
class HelloWorld implements ControllerInterface
{
    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        RouteRunner $route
    ): ResponseInterface {
        $response->getBody()->write("Hello World");
        return $response;
    }
};
```

```php
$router->get("/home", new HelloWorld());
```

# Responding to requests
All controllers **MUST** return an implementation of `\Psr\Http\Message\ResponseInterface`. You can use the premade response object passed into the controller *or* instantiate your own. I recommend taking a look at [HttpSoft/Response](https://httpsoft.org/docs/response/v1/#usage) for prebuilt response types.
```php
$response = new HttpSoft\Response\HtmlResponse('<p>HTML</p>');
$response = new HttpSoft\Response\JsonResponse(['key' => 'value']);
$response = new HttpSoft\Response\JsonResponse("{key: 'value'}");
$response = new HttpSoft\Response\TextResponse('Text');
$response = new HttpSoft\Response\XmlResponse('<xmltag>XML</xmltag>');
$response = new HttpSoft\Response\RedirectResponse('https/example.com');
$response = new HttpSoft\Response\EmptyResponse();
```

# Catchable Routes
Catchable routes are routes that are only invoked when exceptions are thrown while handling a request. To create a catchable route you can do the following...

## Catching

```php
$router->catch(
    Throwable::class,
    static function (
        ServerRequestInterface $request,
        ResponseInterface $response,
        RouteRunner $route
    ) {
        $exception = $route->exception;
        $exceptionString = $exception->getMessage() . "\n" . $exception->getTraceAsString();
        $logs = fopen("./error.log", "w+");
        fwrite($logs, $exceptionString);
        fclose($logs);

        $response->getBody()->write($exceptionString);
        return $response->withHeader("content-type", "text/plain");
    }
);
```
*Note that `$route->exception` will only be instantiated when catching.*
*Also note that catching `\Throwable` will catch EVERY exception but catching `\Exception` will only catch `\Exception`*

## Specific URI's

```php
$router->catch(
    Throwable::class,
    static function (
        ServerRequestInterface $request,
        ResponseInterface $response,
        RouteRunner $route
    ) {
        $response->getBody()->write("{$request->getUri()->getPath()} is not valid");
        return $response;
    },
    "/users/{id}"
)->setParameter("id", "[0-9]{4}");
```
**Note: Catchers are treated just like routes meaning they can have custom parameters**

# Middleware

Middleware is part of the request handling process that comes before the route controller is invoked.

A good example of middleware is making sure the user is an administrator before they go to a restricted page. You could do this in your routes controller for every admin page sure but, that would be redundant.

You can add middleware to a route by invoking the addMiddleware method and supply controller(s). 

**NOTE: The controllers will be invoked in the order they're appended!**

```php
$router->get(
    "/users/{username}",
    static function (
        ServerRequestInterface $request,
        ResponseInterface $response,
        RouteRunner $route
    ): ResponseInterface {
        $response->getBody()->write("Viewing {$route->getParameter("username")}'s profile");
        return $response;
    }
)->setParameter("id", "[0-9]{4}")->addMiddleware(
    static function (
        ServerRequestInterface $request,
        ResponseInterface $response,
        RouteRunner $route
    ): ResponseInterface {
        $users = ["command_string", "realdiegopoptart"];

        if (!in_array(strtolower($route->getParameter("username")), $users)) {
            return $response->withStatus(404);
        }

        return $route->next($request, $response);
    }
);
```

*Another Note: If you don't want to proceed to the next part of request just return a `ResponseInterface` instead of invoking `RouteRunner::Next`

# Postware

Postware is a type of middleware that operates on the response generated by the Controller and can modify the response data before it is sent to the client. While it doesn't sit between the Controller and the View, it does operate on the response after the View has been generated.

Adding postware is just like middleware, just with a different method.

```php
use HttpSoft\Response\EmptyResponse;

$router->get( // runs second
    "/users/{username}",
    static function (
        ServerRequestInterface $request,
        ResponseInterface $response,
        RouteRunner $route
    ): ResponseInterface {
        $response->getBody()->write("Viewing {$route->getParameter("username")}'s profile");
        return $route->next($request, $response);
    }
)->setParameter("username", "[A-z\_]+")->addMiddleware( // runs first
    static function (
        ServerRequestInterface $request,
        ResponseInterface $response,
        RouteRunner $route
    ): ResponseInterface {
        $users = ["command_string", "realdiegopoptart"];

        if (!in_array(strtolower($route->getParameter("username")), $users)) {
            return new EmptyResponse(404)
        }

        return $route->next($request, $response);
    }
)->addPostware( // runs third
    static function (
        ServerRequestInterface $request,
        ResponseInterface $response,
        RouteRunner $route
    ): ResponseInterface {
        $stream = fopen(__DIR__ . "/access-logs.txt", "a");
        fwrite($stream, "{$route->getParameter("username")}'s profile was accessed at " . date("Y-m-d H:i:s") . "\n");
        fclose($stream);

        return $response;
    }
);
```

# Group routes

If you have multiple routes to inherit the same base uri and the same before/after middleware then you can define them inside the group method...

```php
$router->group("/users", static function (Router $router): void {
    $router->get("/", static fn(): TextResponse => new TextResponse("List Users"));

    $router->group(
        "/{id}",
        static function (Router $router): void {
            $router->get(
                "/",
                static function (
                    ServerRequestInterface $request,
                    ResponseInterface $response,
                    RouteRunner $route
                ): ResponseInterface {
                    $response->getBody()->write(" profile");
                    return $route->next($request, $response);
                }
            );

            $router->get(
                "/json",
                static function (
                    ServerRequestInterface $request,
                    ResponseInterface $response,
                    RouteRunner $route
                ): ResponseInterface {
                    $response->getBody()->write(" json object");
                    return $route->next($request, $response);
                }
            );
        },
        middlewares: [
            static function (
                ServerRequestInterface $request,
                ResponseInterface $response,
                RouteRunner $route
            ): ResponseInterface {
                $response = new TextResponse("User {$route->args->id}");
                $response->getBody()->seek(0, SEEK_END);
                return $route->next($request, $response);
            }
        ],
        postwares: [
            static function (
                ServerRequestInterface $request,
                ResponseInterface $response,
                RouteRunner $route
            ): ResponseInterface {
                $response->getBody()->write($route->args->eof);
                return $response;
            }
        ],
        parameters: [
            "id" => "[0-9]{8}"
        ],
        staticArguments: [
            "eof" => "!"
        ]
    );
});
```

# Static Arguments

If you want to pass static arguments to your controller you can do so by using the `addArgument` method on the Route object.

```php
$router->get("/staticPage", static fn($request, $response, $route) => new TextResponse($route->getParameter("path")))
    ->addArgument("path", __DIR__ . "/index.html")
;
```

You would then be able to access the argument in your controller like any other argument. Note that all static arguments will override any arguments that are passed in the URI.

# React/Http Integration

```php
<?php

use HttpSoft\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Tnapf\Router\Router;

require_once __DIR__ . "/../vendor/autoload.php";

$router = new Router();

$router->get("/", static fn(): ResponseInterface => new TextResponse("Hello World!")); // register routes outside the HttpServer closure!!

$http = new React\Http\HttpServer(static function (Psr\Http\Message\ServerRequestInterface $request) use ($router) {
    return $router->run($request); // pass the request to the router
});

$socket = new React\Socket\SocketServer('0.0.0.0:8000');

$http->listen($socket);
```
