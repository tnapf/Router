<?php

namespace Tests\Tnapf\Router;

use PHPUnit\Framework\TestCase;
use Tnapf\Router\Routing\Methods;
use Tnapf\Router\Routing\Route;

class RouteTest extends TestCase
{
    public function testItPrefixesSlash(): void
    {
        $route = Route::new(uri: "test", controller: static fn() => null, method: "GET");
        $this->assertSame("/test", $route->uri);

        $route = Route::new("test", static fn() => null, "/users", Methods::GET);
        $this->assertSame("/users/test", $route->uri);
    }
}
