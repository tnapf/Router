<?php

namespace Tnapf\Router\Exceptions;

class HttpProxyAuthenticationRequired extends HttpException
{
    public const CODE = 407;
    public const PHRASE = "Proxy Authentication Required";
    public const DESCRIPTION
        = "Is similar to 401 (Unauthorized), but indicates that the client needs " .
          "to authenticate itself in order to use a proxy.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/407";
}
