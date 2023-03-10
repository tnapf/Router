<?php

namespace Tnapf\Router\Exceptions;

class HttpRangeNotSatisfiable extends HttpException {
    public const CODE = 416;
    public const PHRASE = "Range Not Satisfiable";
    public const DESCRIPTION = "Indicates that none of the ranges in the request's Range header field overlap the current extent of the selected resource or that the set of ranges requested has been rejected due to invalid ranges or an excessive request of small or overlapping ranges.";
    public const HREF = "https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/416";
}
