<?php

namespace Tnapf\Router\Exceptions;

class HttpLocked extends HttpException
{
    public const CODE = 423;
    public const PHRASE = "Locked";
    public const DESCRIPTION = "Means the source or destination resource of a method 
is locked.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/423";
}
