<?php

namespace Tnapf\Router\Exceptions;

class HttpLengthRequired extends HttpException {
    public const CODE = 411;
    public const PHRASE = "Length Required";
    public const DESCRIPTION = "Indicates that the server refuses to accept the request without a defined Content-Length.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/411";
}
