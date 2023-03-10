<?php

namespace Tnapf\Router\Exceptions;

class HttpConflict extends HttpException {
    public const CODE = 409;
    public const PHRASE = "Conflict";
    public const DESCRIPTION = "Indicates that the request could not be completed due to a conflict with the current state of the resource.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/409";
}
