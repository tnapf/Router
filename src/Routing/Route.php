<?php

namespace Tnapf\Router\Routing;

use InvalidArgumentException;
use Psr\Http\Server\MiddlewareInterface;
use stdClass;
use Tnapf\Router\Enums\Methods;
use Tnapf\Router\Router;
use Psr\Http\Server\RequestHandlerInterface;

class Route
{
    public readonly string $uri;
    private array $methods;
    private array $staticArguments = [];

    /**
      * @var MiddlewareInterface[]
     */
    private array $middleware = [];

    /**
     * @var MiddlewareInterface[]
     */
    private array $postware = [];
    private stdClass $parameters;

    public function __construct(
        protected readonly Router $router,
        string $uri,
        public readonly RequestHandlerInterface $controller,
        Methods ...$methods
    ) {
        if (!str_starts_with($uri, "/") && empty($this->router->getBaseUri())) {
            $uri = "/{$uri}";
        } elseif (!empty($this->router->getBaseUri()) && str_ends_with($uri, "/")) {
            $uri = substr($uri, 0, -1);
        }

        $this->uri = $this->router->getBaseUri() . $uri;

        $this->parameters = new stdClass();

        $this->methods = $methods;
    }

    public static function new(
        Router $router,
        string $uri,
        RequestHandlerInterface $controller,
        Methods ...$methods
    ): self {
        return new self($router, $uri, $controller, ...$methods);
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

    public function addMiddleware(MiddlewareInterface ...$middlewares): self
    {
        foreach ($middlewares as $middleware) {
            $this->middleware[] = $middleware;
        }

        return $this;
    }

    public function addPostware(MiddlewareInterface ...$postwares): self
    {
        foreach ($postwares as $postware) {
            $this->postware[] = $postware;
        }

        return $this;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function getPostware(): array
    {
        return $this->postware;
    }

    public function acceptsMethod(Methods $method): bool
    {
        return in_array($method, $this->methods);
    }

    public function addStaticArgument(string $name, mixed $value): self
    {
        $this->staticArguments[$name] = $value;

        return $this;
    }

    public function getStaticArguments(): array
    {
        return $this->staticArguments;
    }
}
