<?php

namespace HttpCodesApi;

use HttpSoft\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use stdClass;
use Tnapf\Router\Interfaces\RequestHandlerInterface;

class ListCodes implements RequestHandlerInterface
{
    public static function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        stdClass $args,
        callable $next
    ): ResponseInterface {
        return new JsonResponse(GetCode::getCodes());
    }
}
