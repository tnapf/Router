<?php

namespace Tnapf\Router\Enums;

enum Methods: string
{
    case GET = "GET";
    case POST = "POST";
    case PUT = "PUT";
    case DELETE = "DELETE";
    case OPTIONS = "OPTIONS";
    case HEAD = "HEAD";
    case PATCH = "PATCH";
}