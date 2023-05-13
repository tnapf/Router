<?php

namespace Tnapf\Router\Exceptions;

class HttpExpectationFailed extends HttpException
{
    public const CODE = 417;
    public const PHRASE = "Expectation Failed";
    public const DESCRIPTION
        = "Indicates that the expectation given in the request's Expect header field " .
          "could not be met by at least one of the inbound servers.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/417";
}
