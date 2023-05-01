<?php

namespace Tnapf\Router\Exceptions;

class HttpURITooLong extends HttpException
{
    public const CODE = 414;
    public const PHRASE = "URI Too Long";
    public const DESCRIPTION = "Indicates that the server is refusing to service the " .
    "request because the request-target is longer than the " .
    "server is willing to interpret.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/414";
}
