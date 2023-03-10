<?php

namespace Tnapf\Router\Exceptions;

class HttpNotAcceptable extends HttpException {
    public const CODE = 406;
    public const PHRASE = "Not Acceptable";
    public const DESCRIPTION = "Indicates that the target resource does not have a current representation that would be acceptable to the user agent, according to the proactive negotiation header fields received in the request, and the server is unwilling to supply a default representation.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/406";
}
