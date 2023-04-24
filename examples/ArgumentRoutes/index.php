<?php

require_once __DIR__ . "/../../vendor/autoload.php";

use HttpSoft\Response\HtmlResponse;
use HttpSoft\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tnapf\Router\Interfaces\RequestHandlerInterface;
use Tnapf\Router\Router;

class RenderPage implements RequestHandlerInterface
{
    public static function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        stdClass $args,
        callable $next
    ): ResponseInterface {
        $page = realpath($args->page);
        $code = $args->code ?? 200;

        if (!file_exists($page)) {
            throw new InvalidArgumentException("Page {$page} does not exist");
        }

        return new HtmlResponse(file_get_contents($page), $code);
    }
}

class AnonymousRoute implements RequestHandlerInterface
{
    public static function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        stdClass $args,
        callable $next
    ): ResponseInterface {
        $handler = $args->handler ?? fn() => $response;
        $response = $handler($request, $response, $args, $next);

        if (!$response instanceof ResponseInterface) {
            throw new InvalidArgumentException("Anonymous route must return an instance of " . ResponseInterface::class);
        }

        return $response;
    }
}

Router::get("/", RenderPage::class)
    ->addArgument("page", __DIR__ . '/index.html')
;

Router::get("/users", AnonymousRoute::class)
    ->addArgument("handler", function (ServerRequestInterface $request, ResponseInterface $response, stdClass $args, callable $next): ResponseInterface {
        return new JsonResponse([
            "users" => [
                [
                    "id" => 1,
                    "name" => "John Doe",
                ],
                [
                    "id" => 2,
                    "name" => "Jane Doe",
                ],
            ],
        ]);
    })
;

Router::run();
