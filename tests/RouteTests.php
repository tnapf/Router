<?php

namespace Tests\Tnapf\Router;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tnapf\Router\Enums\Methods;
use Tnapf\Router\Router;
use Tnapf\Router\Routing\Route;

class RouteTests extends TestCase
{
    protected Router $router;

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->router = new Router();
    }

    public function createBasicRoute(Methods...$methods): Route
    {
        return new Route($this->router, "home", TestController::class, ...$methods);
    }

    public function testRoutePrependsMissingStartingSlash(): void
    {
        $route = $this->createBasicRoute();
        $this->assertSame("/home", $route->uri, "Route should prepend missing starting slash");
    }

    public function testRouteRejectsInvalidController(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Route($this->router, "/", "InvalidController");
    }

    public function testRouteSettingGettingParameters(): void
    {
        $route = $this->createBasicRoute();
        $route->setParameter("id", "\d+");
        $this->assertSame("(\d+)", $route->getParameter("id"), "Route should set parameter");

        $this->assertSame("{invalid}", $route->getParameter("invalid"), "Route should return the parameter name if it doesn't exist");
    }

    public function testRouteAddingPostware(): void
    {
        $route = $this->createBasicRoute();
        $route->addPostware(TestController::class);
        $this->assertEquals(TestController::class, $route->getPostware()[0], "Route should add postware");

        $this->expectException(InvalidArgumentException::class);
        $route->addPostware("InvalidController");
    }

    public function testRouteAddingMiddleware(): void
    {
        $route = $this->createBasicRoute();
        $route->addMiddleware(TestController::class);
        $this->assertEquals(TestController::class, $route->getMiddleware()[0], "Route should add middleware");

        $this->expectException(InvalidArgumentException::class);
        $route->addMiddleware("InvalidController");
    }

    public function testRouteSettingAndGettingMethods(): void
    {
        $methods = [
            Methods::POST,
            Methods::GET,
        ];

        $route = $this->createBasicRoute(...$methods);

        foreach ($methods as $method) {
            $this->assertTrue($route->acceptsMethod($method), "Route should have method {$method->value}");
        }

        $this->assertFalse($route->acceptsMethod(Methods::PUT), "Route should not have method PUT");
    }

    public function testRouteSettingAndGettingStaticArguments(): void
    {
        $args = [
            "path" => "index.html",
            "context" => [
                "uri" => "/",
            ]
        ];

        $route = $this->createBasicRoute();

        foreach ($args as $key => $value) {
            $route->addStaticArgument($key, $value);
        }

        $this->assertEquals($args, $route->getStaticArguments(), "Route should set and get static arguments");
    }
}
