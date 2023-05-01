<?php

namespace Tests\Tnapf\Router;

use HttpSoft\Message\ServerRequest;
use HttpSoft\Response\JsonResponse;
use HttpSoft\Response\TextResponse;
use PHPUnit\Framework\TestCase;
use Tnapf\Router\Enums\Methods;
use Tnapf\Router\Exceptions\HttpInternalServerError;
use Tnapf\Router\Exceptions\HttpUnauthorized;
use Tnapf\Router\Router;
use Tnapf\Router\Routing\Route;

class RouterTests extends TestCase
{
    public function getTestRoutes()
    {
        return [
            Route::new("/", TestController::class, Methods::GET)
                ->addStaticArgument("body", "index:GET")
            ,
            Route::new("/", TestController::class, Methods::POST)
                ->addStaticArgument("body", "index:POST")
            ,
            Route::new("/", TestController::class, Methods::PUT)
                ->addStaticArgument("body", "index:PUT")
            ,
            Route::new("/", TestController::class, Methods::DELETE)
                ->addStaticArgument("body", "index:DELETE")
            ,
            Route::new("/", TestController::class, Methods::PATCH)
                ->addStaticArgument("body", "index:PATCH")
            ,
            Route::new("/", TestController::class, Methods::HEAD)
                ->addStaticArgument("body", "index:HEAD")
            ,
            Route::new("/", TestController::class, Methods::OPTIONS)
                ->addStaticArgument("body", "index:OPTIONS")
            ,
            Route::new("/testwith/{placeholder}", TestController::class, Methods::GET)
                ->addStaticArgument("handler", function ($req, $res, $args) {
                    unset($args->handler);
                    return new JsonResponse($args);
                })
            ,
            Route::new("/users/{id}", TestController::class, Methods::GET)
                ->addStaticArgument("handler", function ($req, $res, $args) {
                    unset($args->handler);
                    return new TextResponse("User {$args->id}");
                })
                ->setParameter("id", "[0-9]+"),
            Route::new("/401", TestController::class, Methods::GET)
                ->addStaticArgument("handler", fn($req) => throw new HttpUnauthorized($req))
            ,
            Route::new("/no401", TestController::class, Methods::GET)
                ->addStaticArgument("handler", fn($req) => throw new HttpUnauthorized($req))
        ];
    }

    public function registerTestRoutes()
    {
        Router::clearAll();
        foreach ($this->getTestRoutes() as $route) {
            Router::addRoute($route);
        }
    }

    public function testAllRequestTypes(): void
    {
        $this->registerTestRoutes();

        foreach (Methods::cases() as $method) {
            $request = new ServerRequest([], [], [], [], [], $method->value, "/");
            $emitter = new StoreResponseEmitter();

            Router::run($request, $emitter);

            $this->assertEquals("index:{$method->value}", $emitter->getResponse()->getBody()->__toString(), "{$method->value} route failed to resolve");
        }
    }

    public function testStaticPatterns(): void
    {
        $this->registerTestRoutes();
        $request = new ServerRequest([], [], [], [], [], "GET", "/");
        $emitter = new StoreResponseEmitter();

        Router::run($request, $emitter);

        $this->assertEquals("index:GET", $emitter->getResponse()->getBody()->__toString(), "Static routing failed");
    }

    public function testMiddleware(): void
    {
        Router::get("/", TestController::class)
            ->addStaticArgument("body", "2")
            ->addMiddleware(TestMiddleware::class)
        ;

        $request = new ServerRequest([], [], [], [], [], "GET", "/");
        $emitter = new StoreResponseEmitter();

        Router::run($request, $emitter);

        $this->assertEquals("12", $emitter->getResponse()->getBody()->__toString(), "Middleware failed");
    }

    public function testPostware(): void
    {
        Router::get("/", TestController::class)
            ->addStaticArgument("body", "2")
            ->addPostware(TestPostware::class)
        ;

        $request = new ServerRequest([], [], [], [], [], "GET", "/");
        $emitter = new StoreResponseEmitter();

        Router::run($request, $emitter);

        $this->assertEquals("23", $emitter->getResponse()->getBody()->__toString(), "Postware failed");
    }

    public function testPostwareAndMiddleware(): void
    {
        Router::get("/", TestController::class)
            ->addStaticArgument("body", "2")
            ->addMiddleware(TestMiddleware::class)
            ->addPostware(TestPostware::class)
        ;

        $request = new ServerRequest([], [], [], [], [], "GET", "/");
        $emitter = new StoreResponseEmitter();

        Router::run($request, $emitter);

        $this->assertEquals("123", $emitter->getResponse()->getBody()->__toString(), "Middleware and Postware failed");
    }

    public function testDynamicPatterns(): void
    {
        $this->registerTestRoutes();
        $request = new ServerRequest([], [], [], [], [], "GET", "/users/123");
        $emitter = new StoreResponseEmitter();

        Router::run($request, $emitter);

        $this->assertEquals('User 123', $emitter->getResponse()->getBody()->__toString(), "Placeholder regex failed");

        $request = $request->withUri($request->getUri()->withPath("/users/abc"));

        Router::run($request, $emitter);

        $this->assertEquals(404, $emitter->getResponse()->getStatusCode(), "Response should be 404");
    }

    public function testDynamicRegexPatterns(): void
    {
        $request = new ServerRequest([], [], [], [], [], "GET", "/users/1");
        $emitter = new StoreResponseEmitter();

        Router::run($request, $emitter);

        $this->assertEquals("User 1", $emitter->getResponse()->getBody()->__toString(), "Regex routing failed");
    }

    public function testRoutingShorthands(): void
    {
        $emitter = new StoreResponseEmitter();
        Router::all("/all", TestController::class)
            ->addStaticArgument("body", "all")
        ;

        foreach (Methods::cases() as $methodCase) {
            $method = "\Tnapf\Router\Router::" . strtolower($methodCase->value);
            $request = new ServerRequest([], [], [], [], [], $methodCase->value, "/");
            $uri = "/";
            call_user_func($method, $uri, TestController::class)
                ->addStaticArgument("body", $methodCase->value)
            ;

            Router::run($request, $emitter);

            $this->assertEquals("{$methodCase->value}", $emitter->getResponse()->getBody()->__toString(), "{$methodCase->value} shorthand failed");

            $request = $request->withUri($request->getUri()->withPath("/all"));

            Router::run($request, $emitter);

            $this->assertEquals("all", $emitter->getResponse()->getBody()->__toString(), "all shorthand failed");
        }
    }

    public function testHttpExceptions(): void
    {
        $request = new ServerRequest([], [], [], [], [], "GET", "/route");
        $emitter = new StoreResponseEmitter();

        foreach ($this->getAllHttpExceptionClasses() as $exception) {
            Router::addRoute(
                Route::new("/route", TestController::class, Methods::GET)
                    ->addStaticArgument("handler", fn($req) => throw new $exception($req))
            );

            Router::run($request, $emitter);

            $this->assertEquals($exception::CODE, $emitter->getResponse()->getStatusCode(), "{$exception} failed to throw");
        }
    }

    public function testCatching(): void
    {
        $this->registerTestRoutes();
        $request = new ServerRequest([], [], [], [], [], "GET", "/401");
        $emitter = new StoreResponseEmitter();

        Router::catch(HttpUnauthorized::class, TestController::class)
            ->addStaticArgument("body", "Unauthorized")
        ;

        Router::run($request, $emitter);

        $this->assertEquals("Unauthorized", $emitter->getResponse()->getBody()->__toString(), "Catching failed");
    }

    public function testCatcherNotRegisteringTwice(): void
    {
        Router::clearAll();

        Router::catch(HttpUnauthorized::class, TestController::class);
        Router::catch(HttpUnauthorized::class, TestController::class);

        $this->assertEquals(1, count(Router::getCatchers()), "Catcher registered twice");
    }

    public function testThrowingNonHttpException(): void
    {
        Router::clearAll();

        $this->expectException(\Exception::class);

        Router::get("/", TestController::class)
            ->addStaticArgument("handler", fn($req) => throw new \Exception("Test"))
        ;

        $request = new ServerRequest([], [], [], [], [], "GET", "/");
        $emitter = new StoreResponseEmitter();

        Router::run($request, $emitter);
    }

    public function testCustomEmissionTypes(): void
    {
        Router::clearAll();

        Router::get("/", TestController::class)
            ->addStaticArgument("handler", fn($req) => throw new HttpInternalServerError($req))
        ;

        $request = new ServerRequest([], [], [], [], [], "GET", "/");
        $emitter = new StoreResponseEmitter();

        Router::emitHttpExceptions(Router::EMIT_JSON_RESPONSE);
        Router::run($request, $emitter);

        $expectedCode = HttpInternalServerError::CODE;
        $expectedDescription = HttpInternalServerError::getDescription();
        $expectedPhrase = HttpInternalServerError::PHRASE;
        $expectedHref = HttpInternalServerError::HREF;
        $expectedBody = "{\"description\":\"{$expectedDescription}\",\"phrase\":\"{$expectedPhrase}\",\"code\":{$expectedCode},\"href\":\"{$expectedHref}\"}";

        $this->assertEquals("application/json; charset=UTF-8", $emitter->getResponse()->getHeaderLine("Content-Type"), "Custom emission type failed");
        $this->assertEquals($expectedBody, $emitter->getResponse()->getBody()->__toString(), "Custom emission type failed");
    }

    public function testCatchingSpecificUri(): void
    {
        $this->registerTestRoutes();
        $request = new ServerRequest([], [], [], [], [], "GET", "/401");
        $emitter = new StoreResponseEmitter();

        Router::catch(HttpUnauthorized::class, TestController::class, "/401")
            ->addStaticArgument("body", "Unauthorized")
        ;

        Router::run($request, $emitter);

        $this->assertEquals("Unauthorized", $emitter->getResponse()->getBody()->__toString(), "Catching failed");

        $request = $request->withUri($request->getUri()->withPath("/no401"));

        Router::run($request, $emitter);

        $this->assertEquals(401, $emitter->getResponse()->getStatusCode(), "Response should be 401");
    }

    public function testExceptionForImproperCatcher(): void
    {
        Router::clearAll();
        $this->expectException(\InvalidArgumentException::class);

        Router::catch(\stdClass::class, TestController::class);
    }

    public function testGrouping(): void
    {
        Router::clearAll();
        Router::group("/users", function () {
            Router::get("/{id}", TestController::class);
            Router::get("/", TestController::class)
                ->addStaticArgument("body", "1")
            ;
        }, [
            TestMiddleware::class
        ], [
            TestPostware::class
        ], [
            "id" => "[0-9]+"
        ], [
            "body" => "2"
        ]);

        $request = new ServerRequest([], [], [], [], [], "GET", "/users/1234");
        $emitter = new StoreResponseEmitter();

        Router::run($request, $emitter);

        $this->assertEquals("123", $emitter->getResponse()->getBody()->__toString(), "Grouping failed");

        $request = $request->withUri($request->getUri()->withPath("/users"));

        Router::run($request, $emitter);

        $this->assertEquals("113", $emitter->getResponse()->getBody()->__toString(), "Grouping failed");
    }

    public function testNestedGrouping(): void
    {
        Router::clearAll();
        Router::group("/users", function () {
            Router::group("/{id}", function () {
                Router::get("/test", TestController::class);
            });
        }, [
            TestMiddleware::class
        ], [
            TestPostware::class
        ], [
            "id" => "[0-9]+"
        ], [
            "body" => "2"
        ]);

        $request = new ServerRequest([], [], [], [], [], "GET", "/users/1234/test");
        $emitter = new StoreResponseEmitter();

        Router::run($request, $emitter);

        $this->assertEquals("123", $emitter->getResponse()->getBody()->__toString(), "Nested grouping failed");
    }

    public function getAllHttpExceptionClasses(): array
    {
        return [
            \Tnapf\Router\Exceptions\HttpBadRequest::class,
            \Tnapf\Router\Exceptions\HttpUnauthorized::class,
            \Tnapf\Router\Exceptions\HttpPaymentRequired::class,
            \Tnapf\Router\Exceptions\HttpForbidden::class,
            \Tnapf\Router\Exceptions\HttpNotFound::class,
            \Tnapf\Router\Exceptions\HttpMethodNotAllowed::class,
            \Tnapf\Router\Exceptions\HttpNotAcceptable::class,
            \Tnapf\Router\Exceptions\HttpProxyAuthenticationRequired::class,
            \Tnapf\Router\Exceptions\HttpRequestTimeout::class,
            \Tnapf\Router\Exceptions\HttpConflict::class,
            \Tnapf\Router\Exceptions\HttpGone::class,
            \Tnapf\Router\Exceptions\HttpLengthRequired::class,
            \Tnapf\Router\Exceptions\HttpPreconditionFailed::class,
            \Tnapf\Router\Exceptions\HttpPayloadTooLarge::class,
            \Tnapf\Router\Exceptions\HttpURITooLong::class,
            \Tnapf\Router\Exceptions\HttpUnsupportedMediaType::class,
            \Tnapf\Router\Exceptions\HttpRangeNotSatisfiable::class,
            \Tnapf\Router\Exceptions\HttpExpectationFailed::class,
            \Tnapf\Router\Exceptions\HttpImATeapot::class,
            \Tnapf\Router\Exceptions\HttpUnprocessableEntity::class,
            \Tnapf\Router\Exceptions\HttpLocked::class,
            \Tnapf\Router\Exceptions\HttpFailedDependency::class,
            \Tnapf\Router\Exceptions\HttpUpgradeRequired::class,
            \Tnapf\Router\Exceptions\HttpPreconditionRequired::class,
            \Tnapf\Router\Exceptions\HttpTooManyRequests::class,
            \Tnapf\Router\Exceptions\HttpRequestHeaderFieldsTooLarge::class,
            \Tnapf\Router\Exceptions\HttpUnavailableForLegalReasons::class,
            \Tnapf\Router\Exceptions\HttpInternalServerError::class,
            \Tnapf\Router\Exceptions\HttpNotImplemented::class,
            \Tnapf\Router\Exceptions\HttpBadGateway::class,
            \Tnapf\Router\Exceptions\HttpServiceUnavailable::class,
            \Tnapf\Router\Exceptions\HttpGatewayTimeout::class,
            \Tnapf\Router\Exceptions\HttpVersionNotSupported::class,
            \Tnapf\Router\Exceptions\HttpVariantAlsoNegotiates::class,
            \Tnapf\Router\Exceptions\HttpInsufficientStorage::class,
            \Tnapf\Router\Exceptions\HttpNetworkAuthenticationRequired::class
        ];
    }
}