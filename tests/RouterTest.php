<?php

namespace Tests\Tnapf\Router;

use Exception;
use HttpSoft\Emitter\EmitterInterface;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Response\TextResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Tnapf\Router\Exceptions\HttpNotFound;
use Tnapf\Router\Router;
use Tnapf\Router\Routing\Methods;
use Tnapf\Router\Routing\RouteRunner;

class RouterTest extends TestCase
{
    public function newRouter(bool $withCatcher = true): Router
    {
        $router = new Router();

        if ($withCatcher) {
            $router->catch(
                Throwable::class,
                static fn(
                    ServerRequestInterface $request,
                    ResponseInterface $response,
                    RouteRunner $route
                ): TextResponse => new TextResponse((string)$route->exception, 500)
            );

            $router->catch(
                HttpNotFound::class,
                static fn(
                    ServerRequestInterface $request,
                    ResponseInterface $response,
                    RouteRunner $route
                ): TextResponse => new TextResponse($route->exception->getMessage(), 404)
            );
        }

        return $router;
    }

    public function testShorthands(): void
    {
        $router = $this->newRouter();

        foreach (Methods::ALL as $method) {
            $router->{$method}("/", static fn(): TextResponse => new TextResponse($method));
            $request = new ServerRequest(method: $method, uri: "/");
            $response = $router->run($request);

            $this->assertSame($method, (string)$response->getBody());
        }

        $router->clearAll();

        $router->all("/", static fn(): TextResponse => new TextResponse("all"));

        foreach (Methods::ALL as $method) {
            $request = new ServerRequest(method: $method, uri: "/");
            $response = $router->run($request);
            $this->assertSame("all", (string)$response->getBody());
        }
    }

    public function testEmission(): void
    {
        $router = $this->newRouter();

        $router->get("/", static fn(): TextResponse => new TextResponse("Hello World!"));

        $request = new ServerRequest(method: "GET", uri: "/");
        $router->emit($request);

        $this->expectOutputString("Hello World!");
    }

    public function test404(): void
    {
        $router = $this->newRouter();

        $request = new ServerRequest(method: "GET", uri: "/");
        $response = $router->run($request);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test500(): void
    {
        $router = $this->newRouter();

        $router->get("/exception", static function (): void {
            throw new Exception("Test");
        });

        $request = new ServerRequest(method: "GET", uri: "/exception");
        $response = $router->run($request);

        $this->assertSame(500, $response->getStatusCode());
    }

    public function testPlaceholders(): void
    {
        $router = $this->newRouter();

        $router->get("/hello/{name}", static function (
            ServerRequestInterface $request,
            ResponseInterface $response,
            RouteRunner $route
        ): TextResponse {
            return new TextResponse("Hello {$route->args->name}!");
        });

        $request = new ServerRequest(method: "GET", uri: "/hello/world");
        $response = $router->run($request);

        $this->assertSame("Hello world!", (string)$response->getBody());
    }

    public function testCustomEmitter(): void
    {
        $emitter = new class implements EmitterInterface {
            public function emit(ResponseInterface $response, bool $withoutBody = false): void
            {
                echo "Hello World!";
            }
        };

        $router = $this->newRouter();
        $router->emit(new ServerRequest(method: "GET", uri: "/"), $emitter);

        $this->expectOutputString("Hello World!");
    }

    public function testGroupings(): void
    {
        $router = $this->newRouter();

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

        $id = 12345678;
        $requests = [
            "/users" => "List Users",
            "/users/{$id}" => "User {$id} profile!",
            "/users/{$id}/json" => "User {$id} json object!"
        ];

        foreach ($requests as $uri => $expectedBody) {
            $request = new ServerRequest(method: "GET", uri: $uri);

            $response = $router->run($request);
            $body = (string)$response->getBody();

            $this->assertSame($expectedBody, $body);
        }
    }

    public function testCatching(): void
    {
        $router = $this->newRouter();

        $router->get("/catch", static function (): void {
            throw new Exception("Test");
        });

        $router->catch(Exception::class, static function (): ResponseInterface {
            return new TextResponse("Caught!", 500);
        });

        $request = new ServerRequest(method: "GET", uri: "/catch");
        $response = $router->run($request);

        $this->assertSame("Caught!", (string)$response->getBody());
        $this->assertSame(500, $response->getStatusCode());
    }

    public function testNotCatching(): void
    {
        $router = $this->newRouter(false);

        $router->get("/catch", static function (): void {
            throw new Exception("Test");
        });

        $this->expectException(Exception::class);

        $request = new ServerRequest(method: "GET", uri: "/catch");
        $router->run($request);
    }
}
