<?php

namespace Tests\Tnapf\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use Tnapf\Router\Interfaces\RequestHandlerInterface;

class TestPostware implements RequestHandlerInterface {
    public static function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        stdClass $args,
        callable $next
    ): ResponseInterface {
        $response->getBody()->write("3");

        return $next($request, $response, $args, $next);
    }
}