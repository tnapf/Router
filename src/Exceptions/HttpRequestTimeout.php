<?php

namespace Tnapf\Router\Exceptions;

class HttpRequestTimeout extends HttpException
{
    public const CODE = 408;
    public const PHRASE = "Request Timeout";
    public const DESCRIPTION = "Indicates that the server did not receive a complete 
request message within the time that it was prepared 
to wait.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/408";
}
