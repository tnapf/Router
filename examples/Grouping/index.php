<?php

use HttpSoft\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tnapf\Router\Interfaces\RequestHandlerInterface;
use Tnapf\Router\Router;

require_once __DIR__ . "/../../vendor/autoload.php";

// Users table in database
$users = [
    [
        "id" => 1,
        "name" => "John Doe",
        "email" => "johndoe@gmail.com"
    ],
    [
        "id" => 2,
        "name" => "Jane Doe",
        "email" => "janedoe@gmail.com"
    ],
    [
        "id" => 3,
        "name" => "John Smith",
        "email" => "johnsmit@gmai.com"
    ]
];

// JsonResponseController.php
class JsonResponseController implements RequestHandlerInterface
{
    public static function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        stdClass $args,
        callable $next
    ): ResponseInterface {
        $json = $args->data ?? call_user_func($args->fetch ?? static fn() => null, $request, $response, $args, $next);
        $code = $args->code ?? 200;

        if (!is_array($json) && !is_object($json)) {
            throw new InvalidArgumentException("JSON must be an array or object");
        }

        return new JsonResponse($json, $code);
    }
}

// ValidUserId.php
class ValidUserId implements RequestHandlerInterface
{
    public static function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        stdClass $args,
        callable $next
    ): ResponseInterface {
        $users = $args->users;

        $args->user = array_filter($users, static fn(array $user) => $user["id"] === (int)$args->id);

        if (empty($args->user)) {
            return new JsonResponse(["error" => "User not found"], 404);
        }

        return $next($request, $response, $args);
    }
}

// index.php
Router::get("/users", JsonResponseController::class)
    ->addStaticArgument("data", $users)
;

Router::group(
    "/users/{id}",
    static function () {
        Router::get("/", JsonResponseController::class)
            ->addStaticArgument("fetch", static fn(ServerRequestInterface $request, ResponseInterface $response, stdClass $args, callable $next) => $args->user)
        ;
    },
    middlewares: [
        ValidUserId::class,
    ],
    staticArguments: compact('users')
);

Router::run();
