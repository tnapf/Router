<?php

namespace HttpCodesApi;

use HttpSoft\Response\HtmlResponse;
use HttpSoft\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use stdClass;
use Tnapf\Router\Exceptions\HttpNotFound;
use Tnapf\Router\Interfaces\RequestHandlerInterface;
use Tnapf\Router\Routing\Next;

class GetCode implements RequestHandlerInterface
{
    public static function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        stdClass $args,
        ?Next $next = null
    ): ResponseInterface {
        foreach (getCodes() as $code) {
            if ($args->code === $code->code) {
                if ($args->type === "json") {
                    return new JsonResponse($code);
                } else {
                    ob_start();
                    require "./HtmlResponse.php";
                    $html = ob_get_clean();

                    return new HtmlResponse($html);
                }
            }
        }

        return throw new HttpNotFound($request);
    }
}
