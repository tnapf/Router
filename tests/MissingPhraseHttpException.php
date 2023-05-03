<?php

namespace Tests\Tnapf\Router;

use Tnapf\Router\Exceptions\HttpException;

class MissingPhraseHttpException extends HttpException
{
    public const CODE = 0;
    public const PHRASE = "";
    public const DESCRIPTION = 
        "Indicates that the server encountered an unexpected condition that prevented it from fulfilling the request.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/502";
}
