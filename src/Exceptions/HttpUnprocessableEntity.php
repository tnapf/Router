<?php

namespace Tnapf\Router\Exceptions;

class HttpUnprocessableEntity extends HttpException {
    public const CODE = 422;
    public const PHRASE = "Unprocessable Entity";
    public const DESCRIPTION = "Means the server understands the content type of the request entity (hence a 415(Unsupported Media Type) status code is inappropriate), and the syntax of the request entity is correct (thus a 400 (Bad Request) status code is inappropriate) but was unable to process the contained instructions.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/422";
}

