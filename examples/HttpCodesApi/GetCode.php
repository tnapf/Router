<?php

use HttpSoft\Response\HtmlResponse;
use HttpSoft\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tnapf\Router\Exceptions\HttpNotFound;
use Tnapf\Router\Interfaces\RequestHandlerInterface;
use Tnapf\Router\Routing\Next;

class GetCode implements RequestHandlerInterface {
    public static function handle(ServerRequestInterface $request, ResponseInterface $response, stdClass $args, ?Next $next = null): ResponseInterface
    {
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

        return throw new HttpNotFound($request);
    }
}
