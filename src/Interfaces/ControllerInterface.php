<?php

namespace Tnapf\Router\Interfaces;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tnapf\Router\Routing\RouteRunner;

interface ControllerInterface
{
    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        RouteRunner $route
    ): ResponseInterface;
}
