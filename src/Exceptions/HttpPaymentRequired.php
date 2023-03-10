<?php

namespace Tnapf\Router\Exceptions;

class HttpPaymentRequired extends HttpException {
    public const CODE = 402;
    public const PHRASE = "Payment Required";
    public const DESCRIPTION = "*reserved*";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/402";
}
