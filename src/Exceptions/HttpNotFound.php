<?php

namespace Tnapf\Router\Exceptions;

use Exception;

class HttpNotFound extends Exception {
    public function __construct(string $uri)
    {
        parent::__construct("There are no matching routes for {$uri}", 404);
    }
}