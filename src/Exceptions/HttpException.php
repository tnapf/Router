<?php

namespace Tnapf\Router\Exceptions;

use HttpSoft\Response\EmptyResponse;
use HttpSoft\Response\HtmlResponse;
use HttpSoft\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;

abstract class HttpException extends \Exception {
    public const CODE = 0;
    public const DESCRIPTION = "";
    public const PHRASE = "";
    public const HREF = "";

    public function __construct(public readonly ServerRequestInterface $request) {
        parent::__construct(static::DESCRIPTION." ".static::HREF, static::CODE);
    }

    public static function buildEmptyResponse(): EmptyResponse
    {
        return new EmptyResponse(static::CODE);
    }

    public static function buildHtmlResponse(): HtmlResponse
    {
        $code = static::CODE;
        $description = static::DESCRIPTION;
        $phrase = static::PHRASE;
        $href = static::HREF;

        $html = <<<TEMPLATE
            <!DOCTYPE HTML>
            <html lang='en'>
            <head>
                <title>$code - $phrase</title>
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
                    <h1>$code - <a href='$href'>$phrase</a></h1>
                    <hr>
                    <p>$description</p>
                </div>
            </body>
            </html>
        TEMPLATE;

        return new HtmlResponse($html, static::CODE);
    }

    public static function buildJsonResponse(): JsonResponse
    {
        $code = static::CODE;
        $description = static::DESCRIPTION;
        $phrase = static::PHRASE;
        $href = static::HREF;

        return new JsonResponse(compact("code", "description", "phrase", "href"), static::CODE);
    }
}