<?php

namespace Tnapf\Router\Exceptions;

class HttpBadRequest extends HttpException
{
    public const CODE = 400;
    public const PHRASE = "Bad Request";
    public const DESCRIPTION = "Indicates that the server cannot or will not process " .
    "the request because the received syntax is invalid, " .
    "nonsensical, or exceeds some limitation on what the " .
    "server is willing to process.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/400";
}
