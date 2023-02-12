<?php

namespace Tnapf\Router\Routing;

use Closure;
use stdClass;
use Tnapf\Router\Enums\Methods;
use Tnapf\Router\Router;

class Route {
    public readonly string $uri;
    private array $methods;
    private array $before = [];
    private array $after = [];
    private stdClass $parameters;

   /**
    * @param string $uri
    * @param class-string<Controller>|Closure $controller
    * @param Methods ...$methods
    */
    public function __construct(string $uri, public readonly string|Closure $controller, Methods ...$methods) {
        if (!str_starts_with($uri, "/")) {
            $uri = "/{$uri}";
        }

        $this->uri = Router::getBaseUri()."$uri";

        $this->parameters = new stdClass;

        if (!is_subclass_of($controller, Controller::class) && !is_callable($controller)) {
            throw new \InvalidArgumentException("$controller must extend ".Controller::class);
        }

        $this->methods = $methods;
    }

    public function getParameter(string $name): ?string
    {
        return $this->parameters->$name ?? "{{$name}}";
    }

    public function setParameter(string $name, string $pattern): self
    {
        $this->parameters->$name = "({$pattern})";

        return $this;
    }

    public function before(string|Closure ...$controllers): self
    {
        foreach ($controllers as $controller) {
            if (!is_subclass_of($controller, MiddlewareController::class) && !is_callable($controller)) {
                throw new \InvalidArgumentException("$controller must extend ".MiddlewareController::class);
            }

            $this->before[] = $controller;
        }

        return $this;
    }

    public function after(string|Closure ...$controllers): self
    {
        foreach ($controllers as $controller) {
            if (!is_subclass_of($controller, MiddlewareController::class) && !is_callable($controller)) {
                throw new \InvalidArgumentException("$controller must extend ".MiddlewareController::class);
            }

            $this->after[] = $controller;
        }

        return $this;
    }

    public function getBefores(): array
    {
        return $this->before;
    }

    public function getAfters(): array
    {
        return $this->after;
    }

    public function acceptsMethod(Methods $method): bool
    {
        return in_array($method, $this->methods);
    }
}