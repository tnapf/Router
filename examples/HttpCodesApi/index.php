<?php

use HttpCodesApi\CatchInternalServerError;
use HttpCodesApi\GetCode;
use HttpCodesApi\ListCodes;
use Tnapf\Router\Exceptions\HttpInternalServerError;
use Tnapf\Router\Router;

require_once "../../vendor/autoload.php";

function getCodes(): array
{
    return json_decode(file_get_contents(__DIR__."/../../tools/HttpExceptions/HttpCodes.json"));
}

Router::get("/{type}/{code}", GetCode::class)->setParameter("code", "[0-9]{3}")->setParameter("type", "json|html");
Router::get("/", ListCodes::class);

Router::emitHttpExceptions(Router::EMIT_HTML_RESPONSE);

Router::run();
