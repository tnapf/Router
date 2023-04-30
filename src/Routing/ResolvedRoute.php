<?php

namespace Tnapf\Router\Routing;

use stdClass;

readonly class ResolvedRoute
{
    public function __construct(
        public Route $route,
        public stdClass $args
    ) {
    }
}
