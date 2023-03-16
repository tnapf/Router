<?php

namespace Tnapf\Router\Interfaces;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

interface RequestHandlerInterface
{
    public static function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        stdClass $args,
        callable $next
    ): ResponseInterface;
}
