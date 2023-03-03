<?php

namespace Tnapf\Router\Exceptions;

class HttpUpgradeRequired extends HttpException {
    public const CODE = 426;
    public const PHRASE = "Upgrade Required";
    public const DESCRIPTION = "Indicates that the server refuses to perform the request using the current protocol but might be willing to do so after the client upgrades to a different protocol.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/426";
}

