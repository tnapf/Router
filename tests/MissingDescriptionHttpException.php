<?php

namespace Tests\Tnapf\Router;

use Tnapf\Router\Exceptions\HttpException;

class MissingDescriptionHttpException extends HttpException
{
    public const CODE = 500;
    public const PHRASE = "Internal Server Error";
    public const DESCRIPTION = "";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/502";
}
