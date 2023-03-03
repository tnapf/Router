<?php

namespace Tnapf\Router\Exceptions;

class HttpGatewayTimeout extends HttpException {
    public const CODE = 504;
    public const PHRASE = "Gateway Time-out";
    public const DESCRIPTION = "Indicates that the server, while acting as a gateway or proxy, did not receive a timely response from an upstream server it needed to access in order to complete the request.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/504";
}

