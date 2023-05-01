<?php

namespace Tnapf\Router\Exceptions;

class HttpServiceUnavailable extends HttpException
{
    public const CODE = 503;
    public const PHRASE = "Service Unavailable";
    public const DESCRIPTION = "Indicates that the server is currently unable to handle " .
    "the request due to a temporary overload or scheduled " .
    "maintenance, which will likely be alleviated after " .
    "some delay.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/503";
}
