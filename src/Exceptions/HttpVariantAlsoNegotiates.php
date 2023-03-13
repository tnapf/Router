<?php

namespace Tnapf\Router\Exceptions;

class HttpVariantAlsoNegotiates extends HttpException
{
    public const CODE = 506;
    public const PHRASE = "Variant Also Negotiates";
    public const DESCRIPTION = "Indicates that the server has an internal configuration error: the chosen variant resource is configured to engage in transparent content negotiation itself, and is therefore not a proper end point in the negotiation process.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/506";
}
