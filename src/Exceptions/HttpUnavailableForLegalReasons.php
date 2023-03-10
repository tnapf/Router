<?php

namespace Tnapf\Router\Exceptions;

class HttpUnavailableForLegalReasons extends HttpException {
    public const CODE = 451;
    public const PHRASE = "Unavailable For Legal Reasons";
    public const DESCRIPTION = "This status code indicates that the server is denying access to the resource in response to a legal demand.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/451";
}
