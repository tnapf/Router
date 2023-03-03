<?php

namespace Tnapf\Router\Exceptions;

class HttpPayloadTooLarge extends HttpException {
    public const CODE = 413;
    public const PHRASE = "Payload Too Large";
    public const DESCRIPTION = "Indicates that the server is refusing to process a request because the request payload is larger than the server is willing or able to process.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/413";
}

