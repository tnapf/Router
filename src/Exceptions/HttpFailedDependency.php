<?php

namespace Tnapf\Router\Exceptions;

class HttpFailedDependency extends HttpException
{
    public const CODE = 424;
    public const PHRASE = "Failed Dependency";
    public const DESCRIPTION
        = "Means that the method could not be performed on the resource because the " .
          "requested action depended on another action and that action failed.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/424";
}
