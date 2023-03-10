<?php

namespace Tnapf\Router\Exceptions;

class HttpForbidden extends HttpException
{
    public const CODE = 403;
    public const PHRASE = "Forbidden";
    public const DESCRIPTION = "Indicates that the server understood the request but refuses to authorize it.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/403";
}
