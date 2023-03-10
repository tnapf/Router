<?php

namespace Tnapf\Router\Exceptions;

class HttpPreconditionRequired extends HttpException {
    public const CODE = 428;
    public const PHRASE = "Precondition Required";
    public const DESCRIPTION = "Indicates that the origin server requires the request to be conditional.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/428";
}
