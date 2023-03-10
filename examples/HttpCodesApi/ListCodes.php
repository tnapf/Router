<?php

namespace HttpCodesApi;

use HttpSoft\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use stdClass;
use Tnapf\Router\Interfaces\RequestHandlerInterface;
use Tnapf\Router\Routing\Next;

class ListCodes implements RequestHandlerInterface {
    public static function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        stdClass $args,
        ?Next $next = null
    ): ResponseInterface
    {
        return new JsonResponse(getCodes());
    }
}
