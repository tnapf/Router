<?php

namespace Tnapf\Router\Exceptions;

class HttpPreconditionFailed extends HttpException
{
    public const CODE = 412;
    public const PHRASE = "Precondition Failed";
    public const DESCRIPTION = "Indicates that one or more preconditions given in " .
    "the request header fields evaluated to false when tested " .
    "on the server.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/412";
}
