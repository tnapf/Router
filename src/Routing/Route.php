<?php

namespace Tnapf\Router\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use Tnapf\Router\Enums\Methods;
use Tnapf\Router\Interfaces\ControllerInterface;
use Tnapf\Router\Router;

class Route
{
    public readonly string $uri;
    private array $methods;
    private array $staticArguments = [];

    /**
      * @var ControllerInterface[]
     */
    private array $middleware = [];

    /**
     * @var ControllerInterface[]
     */
    private array $postware = [];
    private stdClass $parameters;

    public function __construct(
        protected readonly Router $router,
        string $uri,
        public readonly ControllerInterface $controller,
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
        ControllerInterface $controller,
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

    public function addMiddleware(ControllerInterface ...$middlewares): self
    {
        foreach ($middlewares as $middleware) {
            $this->middleware[] = $middleware;
        }

        return $this;
    }

    public function addPostware(ControllerInterface ...$postwares): self
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
