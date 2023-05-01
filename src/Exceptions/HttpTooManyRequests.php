<?php

namespace Tnapf\Router\Exceptions;

class HttpTooManyRequests extends HttpException
{
    public const CODE = 429;
    public const PHRASE = "Too Many Requests";
    public const DESCRIPTION = "Indicates that the user has sent too many requests " .
    "in a given amount of time (rate limiting).";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/429";
}
