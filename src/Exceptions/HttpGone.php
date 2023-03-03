<?php

namespace Tnapf\Router\Exceptions;

class HttpGone extends HttpException {
    public const CODE = 410;
    public const PHRASE = "Gone";
    public const DESCRIPTION = "Indicates that access to the target resource is no longer available at the origin server and that this condition is likely to be permanent.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/410";
}

