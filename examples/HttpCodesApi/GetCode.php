<?php

namespace HttpCodesApi;

use HttpSoft\Response\HtmlResponse;
use HttpSoft\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use stdClass;
use Tnapf\Router\Exceptions\HttpNotFound;
use Tnapf\Router\Interfaces\RequestHandlerInterface;

class GetCode implements RequestHandlerInterface
{
    public static function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        stdClass $args,
        callable $next
    ): ResponseInterface {
        $codes = self::getCodes();

        if (!($code = $codes[$args->code])) {
            return throw new HttpNotFound($request);
        }

        if ($args->type === "json") {
            return new JsonResponse($code);
        }

        ob_start();
        require_once __DIR__ . "/HtmlResponse.php";
        $html = ob_get_clean();

        return new HtmlResponse($html);
    }

    public static function getCodes(): array
    {
        return json_decode(file_get_contents(__DIR__ . "/../../tools/HttpExceptions/HttpCodes.json"), true);
    }
}
