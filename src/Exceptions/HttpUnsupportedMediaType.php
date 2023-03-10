<?php

namespace Tnapf\Router\Exceptions;

class HttpUnsupportedMediaType extends HttpException {
    public const CODE = 415;
    public const PHRASE = "Unsupported Media Type";
    public const DESCRIPTION = "Indicates that the origin server is refusing to service the request because the payload is in a format not supported by the target resource for this method.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/415";
}
