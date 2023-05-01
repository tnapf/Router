<?php

namespace Tnapf\Router\Exceptions;

class HttpVersionNotSupported extends HttpException
{
    public const CODE = 505;
    public const PHRASE = "Version Not Supported";
    public const DESCRIPTION = "Indicates that the server does not support, or refuses " .
    "to support, the protocol version that was used in the " .
    "request message.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/505";
}
