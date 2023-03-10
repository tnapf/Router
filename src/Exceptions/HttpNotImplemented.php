<?php

namespace Tnapf\Router\Exceptions;

class HttpNotImplemented extends HttpException
{
    public const CODE = 501;
    public const PHRASE = "Not Implemented";
    public const DESCRIPTION = "Indicates that the server does not support the functionality required to fulfill the request.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/501";
}
