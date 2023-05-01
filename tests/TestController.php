<?php

namespace Tests\Tnapf\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use Tnapf\Router\Interfaces\RequestHandlerInterface;

class TestController implements RequestHandlerInterface {
    public static function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        stdClass $args,
        callable $next
    ): ResponseInterface {
        if (isset($args->body)) {
            $response->getBody()->write($args->body);
        }

        if (isset($args->handler)) {
            $response = call_user_func($args->handler, $request, $response, $args);
        }

        return $next($request, $response, $args, $next);
    }
}