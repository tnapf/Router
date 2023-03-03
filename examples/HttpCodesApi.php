<?php

require_once "../vendor/autoload.php";

use HttpSoft\Response\HtmlResponse;
use HttpSoft\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tnapf\Router\Exceptions\HttpInternalServerError;
use Tnapf\Router\Exceptions\HttpNotFound;
use Tnapf\Router\Router;

function getCodes(): array
{
    return json_decode(file_get_contents("../src/Exceptions/Generation/HttpCodes.json"));
}

Router::get("/{type}/{code}", function (ServerRequestInterface $req, ResponseInterface $res, stdClass $args) {
    foreach (getCodes() as $code) {
        if ($args->code === $code->code) {
            if ($args->type === "json") {
                return new JsonResponse($code);
            } else {
                $html = <<<TEMPLATE
                <!DOCTYPE HTML>
                <html lang='en'>
                <head>
                    <title>{$code->code} - {$code->phrase}</title>
                </head>
                <body>
                    <style>
                        * {
                            font-family: Arial, Helvetica, sans-serif;
                            text-align: center;
                        }

                        body {
                            background: #1b1c1d;
                            color: white;
                            padding-top: calc(50vh - 95px);
                        }

                        body > div {
                            max-width: 90%;
                            margin: auto;
                            width: fit-content;
                        }
                    </style>
                    <div>
                        <h1>{$code->code} - <a href='{$code->mdn}'>{$code->phrase}</a></h1>
                        <hr>
                        <p>{$code->description}</p>
                    </div>
                </body>
                </html>
                TEMPLATE;

                return new HtmlResponse($html);
            }
        }
    }

    return throw new HttpNotFound($req);
})->setParameter("code", "[0-9]{3}")->setParameter("type", "json|html");

Router::get("/", function () {
    return new JsonResponse(getCodes());
});

Router::catch(HttpInternalServerError::class, function (ServerRequestInterface $req, ResponseInterface $res, stdClass $args) {
    throw $args->exception;
});

Router::emitHttpExceptions(Router::EMIT_JSON_RESPONSE);
Router::run();