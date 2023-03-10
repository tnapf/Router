<?php

namespace Tnapf\Router\Exceptions;

class HttpRequestHeaderFieldsTooLarge extends HttpException {
    public const CODE = 431;
    public const PHRASE = "Request Header Fields Too Large";
    public const DESCRIPTION = "Indicates that the server is unwilling to process the request because its header fields are too large.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/431";
}
