<?php

namespace Tnapf\Router\Routing;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

abstract class Controller {
    abstract public static function handle(ServerRequestInterface $request, ResponseInterface $response, stdClass $args, ?Closure $next = null): ResponseInterface;
}