<?php

namespace Tests\Tnapf\Router;

use PHPUnit\Framework\TestCase;
use Tnapf\Router\Enums\Methods;
use Tnapf\Router\Routing\Route;

class RouteTests extends TestCase
{
    public function createBasicRoute(Methods...$methods): Route
    {
        return new Route("home", \Tests\Tnapf\Router\TestController::class, ...$methods);
    }

    public function testRoutePrependsMissingStartingSlash(): void
    {
        $route = $this->createBasicRoute();
        $this->assertEquals("/home", $route->uri, "Route should prepend missing starting slash");
    }

    public function testRouteRejectsInvalidController(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Route("/", "InvalidController");
    }

    public function testRouteSettingGettingParameters(): void
    {
        $route = $this->createBasicRoute();
        $route->setParameter("id", "\d+");
        $this->assertEquals("(\d+)", $route->getParameter("id"), "Route should set parameter");

        $this->assertEquals("{invalid}", $route->getParameter("invalid"), "Route should return the parameter name if it doesn't exist");
    }

    public function testRouteAddingPostware(): void
    {
        $route = $this->createBasicRoute();
        $route->addPostware(\Tests\Tnapf\Router\TestController::class);
        $this->assertEquals(\Tests\Tnapf\Router\TestController::class, $route->getPostware()[0], "Route should add postware");

        $this->expectException(\InvalidArgumentException::class);
        $route->addPostware("InvalidController");
    }

    public function testRouteAddingMiddleware(): void
    {
        $route = $this->createBasicRoute();
        $route->addMiddleware(\Tests\Tnapf\Router\TestController::class);
        $this->assertEquals(\Tests\Tnapf\Router\TestController::class, $route->getMiddleware()[0], "Route should add middleware");

        $this->expectException(\InvalidArgumentException::class);
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