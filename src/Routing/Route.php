<?php

namespace Tnapf\Router\Routing;

use Closure;
use InvalidArgumentException;
use ReflectionMethod;
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
    * @param array|Closure $controller
    * @param Methods ...$methods
    */
    public function __construct(string $uri, public readonly array|Closure $controller, Methods ...$methods) {
        if (!str_starts_with($uri, "/")) {
            $uri = "/{$uri}";
        }

        $this->uri = Router::getBaseUri()."$uri";

        $this->parameters = new stdClass;

        self::validateController($controller);

        $this->methods = $methods;
    }


    /**
     * @param array|Closure $controller
     * @return void
     * 
     * @throws InvalidArgumentException
     */
    private static function validateController(array|Closure $controller): void
    {
        if (!is_array($controller)) {
            return;
        }

        $fullMethod = "{$controller[0]}::{$controller[1]}";
        
        if (!method_exists($controller[0], $controller[1])) {
            throw new InvalidArgumentException("{$fullMethod} doesn't exist");
        } else if (!(new ReflectionMethod($controller[0], $controller[1]))->isStatic()) {
            throw new InvalidArgumentException("{$fullMethod} is not a static method");
        }
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

    public function before(array|Closure ...$controllers): self
    {
        foreach ($controllers as $controller) {
            self::validateController($controller);

            $this->before[] = $controller;
        }

        return $this;
    }

    public function after(array|Closure ...$controllers): self
    {
        foreach ($controllers as $controller) {
            self::validateController($controller);

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