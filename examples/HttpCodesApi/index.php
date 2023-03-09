<?php

use Tnapf\Router\Exceptions\HttpInternalServerError;
use Tnapf\Router\Router;

require_once "../../vendor/autoload.php";
require_once "./GetCode.php";
require_once "./ListCodes.php";
require_once "./CatchInternalServerError.php";

function getCodes(): array
{
    return json_decode(file_get_contents(__DIR__."/../../tools/HttpExceptionGeneration/HttpCodes.json"));
}

Router::get("/{type}/{code}", GetCode::class)->setParameter("code", "[0-9]{3}")->setParameter("type", "json|html");
Router::get("/", ListCodes::class);

Router::catch(HttpInternalServerError::class, CatchInternalServerError::class);
Router::emitHttpExceptions(Router::EMIT_JSON_RESPONSE);

Router::run();