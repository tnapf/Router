<?php

namespace Tnapf\Router\Exceptions;

class HttpUnauthorized extends HttpException {
    public const CODE = 401;
    public const PHRASE = "Unauthorized";
    public const DESCRIPTION = "Indicates that the request has not been applied because it lacks valid authentication credentials for the target resource.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/401";
}
