<?php

namespace Tnapf\Router\Exceptions;

class HttpMethodNotAllowed extends HttpException
{
    public const CODE = 405;
    public const PHRASE = "Method Not Allowed";
    public const DESCRIPTION = "Indicates that the method specified in the request-line " .
    "is known by the origin server but not supported by " .
    "the target resource.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/405";
}
