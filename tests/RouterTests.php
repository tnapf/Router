<?php

namespace Tests\Tnapf\Router;

use HttpSoft\Message\ServerRequest;
use HttpSoft\Response\JsonResponse;
use PHPUnit\Framework\TestCase;
use Tnapf\Router\Enums\Methods;
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
        ];
    }

    public function registerTestRoutes()
    {
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

    public function testStaticRouting(): void
    {
        $this->registerTestRoutes();
        $request = new ServerRequest([], [], [], [], [], "GET", "/");
        $emitter = new StoreResponseEmitter();

        Router::run($request, $emitter);

        $this->assertEquals("index:GET", $emitter->getResponse()->getBody()->__toString(), "Static routing failed");
    }

    public function testPlaceholderRouting(): void
    {
        $this->registerTestRoutes();
        $request = new ServerRequest([], [], [], [], [], "GET", "/testwith/test");
        $emitter = new StoreResponseEmitter();

        Router::run($request, $emitter);

        $this->assertEquals('{"placeholder":"test"}', $emitter->getResponse()->getBody()->__toString(), "Placeholder routing failed");
    }

    public function testRoutingShorthands(): void
    {
        $emitter = new StoreResponseEmitter();

        foreach (Methods::cases() as $methodCase) {
            $method = "\Tnapf\Router\Router::" . strtolower($methodCase->value);
            $request = new ServerRequest([], [], [], [], [], $methodCase->value, "/");
            $uri = "/";
            call_user_func($method, $uri, TestController::class)
                ->addStaticArgument("body", $methodCase->value)
            ;

            Router::run($request, $emitter);

            $this->assertEquals("{$methodCase->value}", $emitter->getResponse()->getBody()->__toString(), "{$methodCase->value} shorthand failed");
        }
    }

    public function testHttpExceptions(): void
    {
        $request = new ServerRequest([], [], [], [], [], "GET", "/route");
        $emitter = new StoreResponseEmitter();

        foreach ($this->getAllHttpExceptionClasses() as $exception) {
            Router::addRoute(
                Route::new("/route", TestController::class, Methods::GET)
                    ->addStaticArgument("handler", fn($req, $res) => throw new $exception($req, $res))
            );

            Router::run($request, $emitter);

            $this->assertEquals($exception::CODE, $emitter->getResponse()->getStatusCode(), "{$exception} failed to throw");
        }
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