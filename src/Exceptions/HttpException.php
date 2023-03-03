<?php

namespace Tnapf\Router\Exceptions;

use HttpSoft\Message\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class HttpException extends \Exception {
    public const CODE = 0;
    public const DESCRIPTION = "";
    public const PHRASE = "";
    public const HREF = "";

    public function __construct(public readonly ServerRequestInterface $request, bool $emit = false) {
        parent::__construct(static::DESCRIPTION, static::CODE);
    }

    public static function buildResponse(): ResponseInterface
    {
        return new Response();
    }
}