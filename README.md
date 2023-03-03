# Tnapf/Router

Totally Not Another PHP Framework's Route Component

# Table of Contents

- [Installation](#installation)
- [Basic Usage](#basic-usage)
- [Routing](#routing)
  - [Routing Shorthands](#routing-shorthands)
- [Route Patterns](#route-patterns)
  - [Static Route Patterns](#static-route-patterns)
  - [Dynamic Placeholder-based Route Patterns](#dynamic-placeholder-based-route-patterns)
  - [Dynamic PCRE-based Route Patterns](#dynamic-pcre-based-route-patterns)
- [Controllers](#controllers)
  - [Anonymous Function Controller](#anonymous-function-controller)
  - [Class Controller](#class-controller)
- [Template Engine Integration](#template-engine-integration)
- [Responding to requests](#responding-to-requests)
- [Catchable Routes](#catchable-routes)
  - [Catching](#catching)
  - [Specific URI's](#specific-uris)
  - [Custom Catchables](#custom-catchables)
  - [Available HttpExceptions](#available-httpexceptions)
- [Middleware](#middleware)
  - [Before Middleware](#before-middleware)
  - [After Middleware](#after-middleware)
- [Mounting routes (Group routes)](#mounting-routes-group-routes)

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
use Tnapf\Router\Exceptions\HttpInternalServerError;
use Tnapf\Router\Exceptions\HttpNotFound;

Router::get("/user/{username}", function (ServerRequestInterface $req, ResponseInterface $res, stdClass $args): ResponseInterface {
    $users = ["commandstring", "realdiegopoptart"];

    if (!in_array($args->username, $users)) {
        throw new HttpNotFound($req);
    }

    return new TextResponse("Viewing {$args->username}'s Profile");
})->setParameter("username", "[a-zA-Z_]+");

Router::catch(HttpNotFound::class, function (ServerRequestInterface $req, ResponseInterface $res, stdClass $args): ResponseInterface {
    return new TextResponse("{$args->username} is not registered!");
}, "/user/{username}")->setParameter("username", "[a-zA-Z_]+");

Router::catch(HttpInternalServerError::class, function () {
    return new TextResponse("An internal server error has occurred");
});

Router::catch(HttpNotFound::class, function (ServerRequestInterface $req, ResponseInterface $res, stdClass $args): ResponseInterface {
    return new TextResponse("{$req->getRequestTarget()} is not a valid URI");
});

Router::run();
```

# Routing

You can manually create a route and then store it with the addRoute method

```php
$route = new Route("pattern", function() { /* ... */ }, Methods::GET);

Router::addRoute($route);
```

If you want the same controller to be used for multiple methods you can do the following...

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
Router::all('pattern', function() { /* ... */ });
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

Create a class with a static method to handle the route

```php
class HomeController extends Controller {
    public static function handle(ServerRequestInterface $req, ResponseInterface $res, stdClass $args): ResponseInterface
    {
        $res->getBody()->write("Welcome home!");
        return $res;
    }
}
```

Then insert the class string into an array the first key is the class string and the second is the name of the method

```php
Router::get("/home", [HomeController::class, "handle"]);
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

# Catchable Routes
Catchable routes are routes that are only invoked when exceptions are thrown while handling a request. To create a catchable route you can do the following...

## Catching

```php
Router::catch(HttpException::class, function (ServerRequestInterface $req, ResponseInterface $res, stdClass $args) {
    return new TextResponse("An internal server error has occurred");
});
```
*Note that `$args->exception` will be the exception throw*

## Specific URI's

```php
Router::catch(HttpNotFound::class, function (ServerRequestInterface $req, ResponseInterface $res, stdClass $args): ResponseInterface {
    return new TextResponse("{$req->getRequestTarget()} is not a valid URI");
}, "/users/{username}");
```
**Note: Catchers are treated just like routes meaning they can have custom parameters as shown in [Basic Usage](#basic-usage)**

## Custom Catchables

By default, you can only catch the exceptions shown below but let's say you make a custom exception named `UserNotFound` and want to have a custom response emitted when it's thrown...well you can...

```php
Router::makeCatchable(UserNotFound::class)
```

and then catch it like a regular HttpException.

## Available HttpExceptions

| Code | Phrase | ClassName |
|------|--------|-----------|
|400|[Bad Request](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/400)|[HttpBadRequest](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpBadRequest.php)
|401|[Unauthorized](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/401)|[HttpUnauthorized](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpUnauthorized.php)
|402|[Payment Required](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/402)|[HttpPaymentRequired](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpPaymentRequired.php)
|403|[Forbidden](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/403)|[HttpForbidden](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpForbidden.php)
|404|[Not Found](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/404)|[HttpNotFound](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpNotFound.php)
|405|[Method Not Allowed](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/405)|[HttpMethodNotAllowed](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpMethodNotAllowed.php)
|406|[Not Acceptable](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/406)|[HttpNotAcceptable](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpNotAcceptable.php)
|407|[Proxy Authentication Required](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/407)|[HttpProxyAuthenticationRequired](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpProxyAuthenticationRequired.php)
|408|[Request Timeout](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/408)|[HttpRequestTimeout](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpRequestTimeout.php)
|409|[Conflict](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/409)|[HttpConflict](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpConflict.php)
|410|[Gone](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/410)|[HttpGone](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpGone.php)
|411|[Length Required](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/411)|[HttpLengthRequired](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpLengthRequired.php)
|412|[Precondition Failed](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/412)|[HttpPreconditionFailed](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpPreconditionFailed.php)
|413|[Payload Too Large](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/413)|[HttpPayloadTooLarge](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpPayloadTooLarge.php)
|414|[URI Too Long](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/414)|[HttpURITooLong](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpURITooLong.php)
|415|[Unsupported Media Type](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/415)|[HttpUnsupportedMediaType](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpUnsupportedMediaType.php)
|416|[Range Not Satisfiable](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/416)|[HttpRangeNotSatisfiable](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpRangeNotSatisfiable.php)
|417|[Expectation Failed](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/417)|[HttpExpectationFailed](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpExpectationFailed.php)
|418|[I'm a teapot](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/418)|[HttpImateapot](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpImateapot.php)
|422|[Unprocessable Entity](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/422)|[HttpUnprocessableEntity](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpUnprocessableEntity.php)
|423|[Locked](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/423)|[HttpLocked](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpLocked.php)
|424|[Failed Dependency](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/424)|[HttpFailedDependency](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpFailedDependency.php)
|426|[Upgrade Required](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/426)|[HttpUpgradeRequired](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpUpgradeRequired.php)
|428|[Precondition Required](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/428)|[HttpPreconditionRequired](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpPreconditionRequired.php)
|429|[Too Many Requests](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/429)|[HttpTooManyRequests](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpTooManyRequests.php)
|431|[Request Header Fields Too Large](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/431)|[HttpRequestHeaderFieldsTooLarge](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpRequestHeaderFieldsTooLarge.php)
|451|[Unavailable For Legal Reasons](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/451)|[HttpUnavailableForLegalReasons](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpUnavailableForLegalReasons.php)
|500|[Internal Server Error](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/500)|[HttpInternalServerError](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpInternalServerError.php)
|501|[Not Implemented](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/501)|[HttpNotImplemented](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpNotImplemented.php)
|502|[Bad Gateway](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/502)|[HttpBadGateway](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpBadGateway.php)
|503|[Service Unavailable](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/503)|[HttpServiceUnavailable](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpServiceUnavailable.php)
|504|[Gateway Time-out](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/504)|[HttpGatewayTimeout](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpGatewayTimeout.php)
|505|[HTTP Version Not Supported](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/505)|[HttpHTTPVersionNotSupported](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpHTTPVersionNotSupported.php)
|506|[Variant Also Negotiates](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/506)|[HttpVariantAlsoNegotiates](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpVariantAlsoNegotiates.php)
|507|[Insufficient Storage](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/507)|[HttpInsufficientStorage](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpInsufficientStorage.php)
|511|[Network Authentication Required](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/511)|[HttpNetworkAuthenticationRequired](https://github.com/tnapf/Router/blob/main/src/Exceptions/HttpNetworkAuthenticationRequired.php)

# Middleware

Middleware is software that connects the model and view in an MVC application, facilitating the communication and data flow between these two components while also providing a layer of abstraction, decoupling the model and view and allowing them to interact without needing to know the details of how the other component operates.

A good example is having before middleware that makes sure the user is an administrator before they go to a restricted page. You could do this in your routes controller for every admin page but that would be redundant. Or for after middleware, you may have a REST API that returns a JSON response. You can have after middleware to make sure the JSON response isn't malformed.

## Before Middleware

You can add middleware to a route by invoking the before method and supply controller(s). 

**NOTE: The controllers will be invoked in the order they're appended!**

```php
Router::get("/user/{username}", function (ServerRequestInterface $req, ResponseInterface $res, stdClass $args): ResponseInterface {
    $users = ["cmdstr", "realdiegopoptart"];

    if (!in_array($args->username, $users)) {
        throw new HttpNotFound($req);
    }

    $res->getBody()->write(" {$args->username}'s profile");

    return $res;
})->setParameter("username", "[a-zA-Z_]+")->before(function (ServerRequestInterface $request, ResponseInterface $response, stdClass $args, Closure $next): ResponseInterface
{
    $res = new TextResponse("Viewing");

    $res->getBody()->seek(0, SEEK_END);

    return $next($res); // will go to the next part of middleware
});
```

*Note: If you don't want to proceed to the next part of middleware just return a `ResponseInterface` instead of passing response to the `$next` closure*

You can also include a class string just like the controller

*Special note about middleware, you can pass variables from beforeMiddleware to the main route or from the main route to afterMiddleware by supplying it as the second argument in the next closure. These variables will be added as an additional argument in the next piece of middleware*

## After Middleware

Adding after middleware is just like before middleware, just with a different method.

```php
Router::get("/user/{username}", function (ServerRequestInterface $req, ResponseInterface $res, stdClass $args, Closure $next): ResponseInterface {
    $users = ["cmdstr", "realdiegopoptart"];

    if (!in_array($args->username, $users)) {
        throw new HttpNotFound($req);
    }

    $res = new TextResponse("Viewing {$args->username}'s ");

    $res->getBody()->seek(0, SEEK_END);

    return $next($res);
})->setParameter("username", "[a-zA-Z_]+")->after(function (ServerRequestInterface $req, ResponseInterface $res, stdClass $args): ResponseInterface
{
    $res->getBody()->write("profile");

    return $res;
});
```

# Mounting routes (Group routes)

If you would multiple routes to inherit the same prefix URI and before/after middleware then you can define them inside the mount method...

```php
Router::mount("/app", function () {
    Router::get("/", function (ServerRequestInterface $req, ResponseInterface $res, stdClass $args, int $userId) {
        return new TextResponse("Welcome Home!\nUserId: $userId");
    });

    Router::get("/channel/{channel}", function (ServerRequestInterface $req, ResponseInterface $res, stdClass $args, int $userId) {
        return new TextResponse("Looking at channelId {$args->channel} as UserId $userId");
    })->setParameter("channel", "\d+");

    // You can now mount inside of a mount
    Router::mount("/api", function () {
        Router::get("/channel/{channel}", function (ServerRequestInterface $req, ResponseInterface $res, stdClass $args, int $userId) {
            return new JsonResponse(["Looking at channelId {$args->channel} as UserId $userId"]);
        })->setParameter("channel", "\d+");

        Router::catch(HttpNotFound::class, function (ServerRequestInterface $req, ResponseInterface $res, stdClass $args) {
            return new JsonResponse(["Channel id {$args->channel} is invalid"]);
        }, "/channel/{channel}")->setParameter("channel", "[a-zA-Z]+");

        Router::catch(HttpNotFound::class, function () {
            return new JsonResponse(["Api Endpoint Not Found"]);
        });
    }, [ /* added to the end of already established before/after middleware */ ]);

    Router::catch(HttpNotFound::class, function () {
        return new TextResponse("App Route Not Found");
    });
    
    Router::catch(HttpNotFound::class, function (ServerRequestInterface $req, ResponseInterface $res, stdClass $args) {
        return new TextResponse("Channel id {$args->channel} is invalid");
    }, "/channel/{channel}")->setParameter("channel", "[a-zA-Z]+");
}, [ // before middleware here
    function (ServerRequestInterface $request, ResponseInterface $response, stdClass $args, ?Closure $next = null): ResponseInterface
    {
        return $next($response, mt_rand(0, 100));
    }
], [ /* after middleware here */ ]);
```
