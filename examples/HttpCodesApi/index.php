<?php

use HttpCodesApi\GetCode;
use HttpCodesApi\ListCodes;
use Tnapf\Router\Router;

require_once __DIR__ . "/../../vendor/autoload.php";

Router::get("/{type}/{code}", GetCode::class)
    ->setParameter("code", "[0-9]{3}")
    ->setParameter("type", "json|html")
;

Router::get("/", ListCodes::class);

Router::emitHttpExceptions(Router::EMIT_HTML_RESPONSE);

Router::run();
