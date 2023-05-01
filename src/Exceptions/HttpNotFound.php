<?php

namespace Tnapf\Router\Exceptions;

class HttpNotFound extends HttpException
{
    public const CODE = 404;
    public const PHRASE = "Not Found";
    public const DESCRIPTION
        = "Indicates that the origin server did not find a current representation " .
          "for the target resource or is not willing to disclose that one exists.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/404";
}
