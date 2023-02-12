<?php

namespace Tnapf\Router\Exceptions;

use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class HttpInternalServerError extends Exception {
    public function __construct(public readonly ServerRequestInterface $request, public readonly Throwable $exception)
    {
        parent::__construct("{$request->getRequestTarget()} has thrown {$exception->getMessage()}", 500);

    }
}