<?php

namespace Tnapf\Router\Exceptions;

class HttpImateapot extends HttpException {
    public const CODE = 418;
    public const PHRASE = "I'm a teapot";
    public const DESCRIPTION = "Any attempt to brew coffee with a teapot should result in the error code 418 I'm a teapot.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/418";
}
