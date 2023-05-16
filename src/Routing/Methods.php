<?php

namespace Tnapf\Router\Routing;

class Methods
{
    public const GET = "GET";
    public const POST = "POST";
    public const PUT = "PUT";
    public const DELETE = "DELETE";
    public const OPTIONS = "OPTIONS";
    public const HEAD = "HEAD";
    public const PATCH = "PATCH";

    public const ALL = [
        self::GET,
        self::POST,
        self::PUT,
        self::DELETE,
        self::OPTIONS,
        self::HEAD,
        self::PATCH
    ];
}
