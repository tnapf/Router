<?php

namespace Tests\Tnapf\Router;

use HttpSoft\Message\ServerRequest;
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
            Route::new("/test", TestController::class, Methods::GET)
                ->addStaticArgument("body", "longeruri:POST")
            ,
            Route::new("/testwith/{placeholder}", TestController::class, Methods::GET)
                ->addStaticArgument("body", "test:GET")
                ->addStaticArgument("placeholder", "placeholder")
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
}