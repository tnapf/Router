<?php

namespace Tnapf\Router\Exceptions;

use Exception;
use Psr\Http\Message\ServerRequestInterface;

class HttpNotFound extends Exception {
    public function __construct(public readonly ServerRequestInterface $request)
    {
        parent::__construct("There are no matching routes for {$request->getRequestTarget()}", 404);
    }
}