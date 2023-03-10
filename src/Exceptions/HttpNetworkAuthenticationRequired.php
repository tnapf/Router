<?php

namespace Tnapf\Router\Exceptions;

class HttpNetworkAuthenticationRequired extends HttpException {
    public const CODE = 511;
    public const PHRASE = "Network Authentication Required";
    public const DESCRIPTION = "Indicates that the client needs to authenticate to gain network access.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/511";
}
