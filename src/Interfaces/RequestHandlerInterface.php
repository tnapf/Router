<?php

namespace Tnapf\Router\Interfaces;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use Tnapf\Router\Routing\Next;

interface RequestHandlerInterface {
    public static function handle(ServerRequestInterface $request, ResponseInterface $response, stdClass $args, Next $next): ResponseInterface;
}