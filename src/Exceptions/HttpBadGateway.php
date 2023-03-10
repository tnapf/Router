<?php

namespace Tnapf\Router\Exceptions;

class HttpBadGateway extends HttpException {
    public const CODE = 502;
    public const PHRASE = "Bad Gateway";
    public const DESCRIPTION = "Indicates that the server, while acting as a gateway or proxy, received an invalid response from an inbound server it accessed while attempting to fulfill the request.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/502";
}

