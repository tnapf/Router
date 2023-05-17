<?php

namespace Tnapf\Router\Handlers;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Closure;
use Tnapf\Router\Interfaces\ControllerInterface;
use Tnapf\Router\Routing\RouteRunner;

class ClosureRequestHandler implements ControllerInterface
{
    public function __construct(
        public readonly Closure $closure
    ) {
    }

    public static function new(callable $closure): self
    {
        return new self($closure);
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        RouteRunner $route
    ): ResponseInterface {
        return ($this->closure)($request, $response, $route);
    }
}
