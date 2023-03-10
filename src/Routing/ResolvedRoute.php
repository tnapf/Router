<?php

namespace Tnapf\Router\Routing;

use stdClass;

class ResolvedRoute {
    public function __construct(
        public readonly Route $route, 
        public readonly stdClass $args
    ) {}
}
