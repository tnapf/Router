<?php

namespace Tnapf\Router\Exceptions;

class HttpInsufficientStorage extends HttpException {
    public const CODE = 507;
    public const PHRASE = "Insufficient Storage";
    public const DESCRIPTION = "Means the method could not be performed on the resource because the server is unable to store the representation needed to successfully complete the request.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/507";
}

