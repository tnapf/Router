<?php

namespace Tests\Tnapf\Router;

use Exception;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Response\JsonResponse;
use HttpSoft\Response\TextResponse;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;
use Tnapf\Router\Enums\Methods;
use Tnapf\Router\Exceptions\HttpBadGateway;
use Tnapf\Router\Exceptions\HttpBadRequest;
use Tnapf\Router\Exceptions\HttpConflict;
use Tnapf\Router\Exceptions\HttpException;
use Tnapf\Router\Exceptions\HttpExpectationFailed;
use Tnapf\Router\Exceptions\HttpFailedDependency;
use Tnapf\Router\Exceptions\HttpForbidden;
use Tnapf\Router\Exceptions\HttpGatewayTimeout;
use Tnapf\Router\Exceptions\HttpGone;
use Tnapf\Router\Exceptions\HttpImATeapot;
use Tnapf\Router\Exceptions\HttpInsufficientStorage;
use Tnapf\Router\Exceptions\HttpInternalServerError;
use Tnapf\Router\Exceptions\HttpLengthRequired;
use Tnapf\Router\Exceptions\HttpLocked;
use Tnapf\Router\Exceptions\HttpMethodNotAllowed;
use Tnapf\Router\Exceptions\HttpNetworkAuthenticationRequired;
use Tnapf\Router\Exceptions\HttpNotAcceptable;
use Tnapf\Router\Exceptions\HttpNotFound;
use Tnapf\Router\Exceptions\HttpNotImplemented;
use Tnapf\Router\Exceptions\HttpPayloadTooLarge;
use Tnapf\Router\Exceptions\HttpPaymentRequired;
use Tnapf\Router\Exceptions\HttpPreconditionFailed;
use Tnapf\Router\Exceptions\HttpPreconditionRequired;
use Tnapf\Router\Exceptions\HttpProxyAuthenticationRequired;
use Tnapf\Router\Exceptions\HttpRangeNotSatisfiable;
use Tnapf\Router\Exceptions\HttpRequestHeaderFieldsTooLarge;
use Tnapf\Router\Exceptions\HttpRequestTimeout;
use Tnapf\Router\Exceptions\HttpServiceUnavailable;
use Tnapf\Router\Exceptions\HttpTooManyRequests;
use Tnapf\Router\Exceptions\HttpUnauthorized;
use Tnapf\Router\Exceptions\HttpUnavailableForLegalReasons;
use Tnapf\Router\Exceptions\HttpUnprocessableEntity;
use Tnapf\Router\Exceptions\HttpUnsupportedMediaType;
use Tnapf\Router\Exceptions\HttpUpgradeRequired;
use Tnapf\Router\Exceptions\HttpURITooLong;
use Tnapf\Router\Exceptions\HttpVariantAlsoNegotiates;
use Tnapf\Router\Exceptions\HttpVersionNotSupported;
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
                ->addStaticArgument("handler", static function ($req, $res, $args) {
                    unset($args->handler);
                    return new JsonResponse($args);
                })
            ,
            Route::new("/users/{id}", TestController::class, Methods::GET)
                ->addStaticArgument("handler", static function ($req, $res, $args) {
                    unset($args->handler);
                    return new TextResponse("User {$args->id}");
                })
                ->setParameter("id", "[0-9]+"),
            Route::new("/401", TestController::class, Methods::GET)
                ->addStaticArgument("handler", static fn($req) => throw new HttpUnauthorized($req))
            ,
            Route::new("/no401", TestController::class, Methods::GET)
                ->addStaticArgument("handler", static fn($req) => throw new HttpUnauthorized($req))
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

            $this->assertEquals("index:{$method->value}", (string)$emitter->getResponse()->getBody(), "{$method->value} route failed to resolve");
        }
    }

    public function testStaticPatterns(): void
    {
        $this->registerTestRoutes();
        $request = new ServerRequest([], [], [], [], [], "GET", "/");
        $emitter = new StoreResponseEmitter();

        Router::run($request, $emitter);

        $this->assertEquals("index:GET", (string)$emitter->getResponse()->getBody(), "Static routing failed");
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

        $this->assertEquals("12", (string)$emitter->getResponse()->getBody(), "Middleware failed");
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

        $this->assertEquals("23", (string)$emitter->getResponse()->getBody(), "Postware failed");
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

        $this->assertEquals("123", (string)$emitter->getResponse()->getBody(), "Middleware and Postware failed");
    }

    public function testDynamicPatterns(): void
    {
        $this->registerTestRoutes();
        $request = new ServerRequest([], [], [], [], [], "GET", "/users/123");
        $emitter = new StoreResponseEmitter();

        Router::run($request, $emitter);

        $this->assertEquals('User 123', (string)$emitter->getResponse()->getBody(), "Placeholder regex failed");

        $request = $request->withUri($request->getUri()->withPath("/users/abc"));

        Router::run($request, $emitter);

        $this->assertEquals(404, $emitter->getResponse()->getStatusCode(), "Response should be 404");
    }

    public function testDynamicRegexPatterns(): void
    {
        $request = new ServerRequest([], [], [], [], [], "GET", "/users/1");
        $emitter = new StoreResponseEmitter();

        Router::run($request, $emitter);

        $this->assertEquals("User 1", (string)$emitter->getResponse()->getBody(), "Regex routing failed");
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
            $method($uri, TestController::class)
                ->addStaticArgument("body", $methodCase->value)
            ;

            Router::run($request, $emitter);

            $this->assertEquals($methodCase->value, (string)$emitter->getResponse()->getBody(), "{$methodCase->value} shorthand failed");

            $request = $request->withUri($request->getUri()->withPath("/all"));

            Router::run($request, $emitter);

            $this->assertEquals("all", (string)$emitter->getResponse()->getBody(), "all shorthand failed");
        }
    }

    public function testHttpExceptions(): void
    {
        $request = new ServerRequest([], [], [], [], [], "GET", "/route");
        $emitter = new StoreResponseEmitter();

        foreach ($this->getAllHttpExceptionClasses() as $exception) {
            Router::addRoute(
                Route::new("/route", TestController::class, Methods::GET)
                    ->addStaticArgument("handler", static fn($req) => throw new $exception($req))
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

        $this->assertEquals("Unauthorized", (string)$emitter->getResponse()->getBody(), "Catching failed");
    }

    public function testCatcherNotRegisteringTwice(): void
    {
        Router::clearAll();

        Router::catch(HttpUnauthorized::class, TestController::class);
        Router::catch(HttpUnauthorized::class, TestController::class);

        $this->assertCount(1, Router::getCatchers(), "Catcher registered twice");
    }

    public function testThrowingNonHttpException(): void
    {
        Router::clearAll();

        $this->expectException(Exception::class);

        Router::get("/", TestController::class)
            ->addStaticArgument("handler", static fn($req) => throw new Exception("Test"))
        ;

        $request = new ServerRequest([], [], [], [], [], "GET", "/");
        $emitter = new StoreResponseEmitter();

        Router::run($request, $emitter);
    }

    public function testCustomEmissionTypes(): void
    {
        Router::clearAll();

        Router::get("/", TestController::class)
            ->addStaticArgument("handler", static fn($req) => throw new HttpInternalServerError($req))
        ;

        $request = new ServerRequest([], [], [], [], [], "GET", "/");
        $emitter = new StoreResponseEmitter();

        Router::emitHttpExceptions(Router::EMIT_JSON_RESPONSE);
        Router::run($request, $emitter);

        $expectedCode = HttpInternalServerError::CODE;
        $expectedDescription = HttpInternalServerError::DESCRIPTION;
        $expectedPhrase = HttpInternalServerError::PHRASE;
        $expectedHref = HttpInternalServerError::HREF;
        $expectedBody = "{\"description\":\"{$expectedDescription}\",\"phrase\":\"{$expectedPhrase}\",\"code\":{$expectedCode},\"href\":\"{$expectedHref}\"}";

        $this->assertEquals("application/json; charset=UTF-8", $emitter->getResponse()->getHeaderLine("Content-Type"), "Custom emission type failed");
        $this->assertEquals($expectedBody, (string)$emitter->getResponse()->getBody(), "Custom emission type failed");
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

        $this->assertEquals("Unauthorized", (string)$emitter->getResponse()->getBody(), "Catching failed");

        $request = $request->withUri($request->getUri()->withPath("/no401"));

        Router::run($request, $emitter);

        $this->assertEquals(401, $emitter->getResponse()->getStatusCode(), "Response should be 401");
    }

    public function testExceptionForImproperCatcher(): void
    {
        Router::clearAll();
        $this->expectException(InvalidArgumentException::class);

        Router::catch(stdClass::class, TestController::class);
    }

    public function testGrouping(): void
    {
        Router::clearAll();
        Router::group(
            "/users",
            static function () {
                Router::get("/{id}", TestController::class);
                Router::get("/", TestController::class)
                    ->addStaticArgument("body", "1");
            },
            [TestMiddleware::class],
            [TestPostware::class],
            ["id" => "[0-9]+"],
            ["body" => "2"]
        );

        $request = new ServerRequest([], [], [], [], [], "GET", "/users/1234");
        $emitter = new StoreResponseEmitter();

        Router::run($request, $emitter);

        $this->assertEquals("123", (string)$emitter->getResponse()->getBody(), "Grouping failed");

        $request = $request->withUri($request->getUri()->withPath("/users"));

        Router::run($request, $emitter);

        $this->assertEquals("113", (string)$emitter->getResponse()->getBody(), "Grouping failed");
    }

    public function testNestedGrouping(): void
    {
        Router::clearAll();
        Router::group("/users", static function () {
            Router::group("/{id}", static function () {
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

        $this->assertEquals("123", (string)$emitter->getResponse()->getBody(), "Nested grouping failed");
    }

    /**
     * @return HttpException[]
     */
    public function getAllHttpExceptionClasses(): array
    {
        return [
            HttpBadRequest::class,
            HttpUnauthorized::class,
            HttpPaymentRequired::class,
            HttpForbidden::class,
            HttpNotFound::class,
            HttpMethodNotAllowed::class,
            HttpNotAcceptable::class,
            HttpProxyAuthenticationRequired::class,
            HttpRequestTimeout::class,
            HttpConflict::class,
            HttpGone::class,
            HttpLengthRequired::class,
            HttpPreconditionFailed::class,
            HttpPayloadTooLarge::class,
            HttpURITooLong::class,
            HttpUnsupportedMediaType::class,
            HttpRangeNotSatisfiable::class,
            HttpExpectationFailed::class,
            HttpImATeapot::class,
            HttpUnprocessableEntity::class,
            HttpLocked::class,
            HttpFailedDependency::class,
            HttpUpgradeRequired::class,
            HttpPreconditionRequired::class,
            HttpTooManyRequests::class,
            HttpRequestHeaderFieldsTooLarge::class,
            HttpUnavailableForLegalReasons::class,
            HttpInternalServerError::class,
            HttpNotImplemented::class,
            HttpBadGateway::class,
            HttpServiceUnavailable::class,
            HttpGatewayTimeout::class,
            HttpVersionNotSupported::class,
            HttpVariantAlsoNegotiates::class,
            HttpInsufficientStorage::class,
            HttpNetworkAuthenticationRequired::class
        ];
    }
}
