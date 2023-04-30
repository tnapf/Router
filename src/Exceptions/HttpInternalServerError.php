<?php

namespace Tnapf\Router\Exceptions;

class HttpInternalServerError extends HttpException
{
    public const CODE = 500;
    public const PHRASE = "Internal Server Error";
    public const DESCRIPTION = "Indicates that the server encountered an unexpected 
condition that prevented it from fulfilling the request.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/500";
}
