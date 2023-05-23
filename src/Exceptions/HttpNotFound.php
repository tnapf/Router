<?php

namespace Tnapf\Router\Exceptions;

use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class HttpNotFound extends Exception
{
    public function __construct(
        public readonly ServerRequestInterface $request,
        int $code = 404,
        ?Throwable $previous = null
    ) {
        parent::__construct("No controller bound to {$request->getUri()->getPath()}!", $code, $previous);
    }
}
