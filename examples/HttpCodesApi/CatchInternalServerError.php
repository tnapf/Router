<?php

namespace HttpCodesApi;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use stdClass;
use Tnapf\Router\Exceptions\HttpInternalServerError;
use Tnapf\Router\Interfaces\RequestHandlerInterface;
use Tnapf\Router\Routing\Next;

class CatchInternalServerError implements RequestHandlerInterface {
    public static function handle(ServerRequestInterface $request, ResponseInterface $response, stdClass $args, ?Next $next = null): ResponseInterface
    {
        $logs = fopen("./error.log", "w+");
        fwrite($logs, $args->exception->__toString());
        fclose($logs);
        
        return HttpInternalServerError::buildHtmlResponse();
    }
}
