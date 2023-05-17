<?php

namespace Tnapf\Router\Routing;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use Tnapf\Router\Handlers\ClosureRequestHandler;
use Tnapf\Router\Routing\Methods;
use Tnapf\Router\Interfaces\ControllerInterface;
use Tnapf\Router\Router;

use function var_dump;

class Route
{
    public readonly string $uri;

    /**
     * @var string[]
     */
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
        string $uri,
        public readonly ControllerInterface $controller,
        protected readonly string $baseUri = "",
        string ...$methods
    ) {
        if (!str_starts_with($uri, "/")) {
            $uri = "/{$uri}";
        }

        if (!empty($this->baseUri) && str_ends_with($uri, "/")) {
            $uri = substr($uri, 0, -1);
        }

        $this->uri = $this->baseUri . $uri;

        $this->parameters = new stdClass();

        $this->methods = $methods;
    }

    public static function new(
        string $uri,
        ControllerInterface|Closure $controller,
        string $baseUri = "",
        string ...$methods
    ): self {
        if ($controller instanceof Closure) {
            $controller = new ClosureRequestHandler($controller);
        }

        return new self($uri, $controller, $baseUri, ...$methods);
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

    public function addMiddleware(ControllerInterface|Closure ...$middlewares): self
    {
        foreach ($middlewares as $middleware) {
            if ($middleware instanceof Closure) {
                $middleware = new ClosureRequestHandler($middleware);
            }

            $this->middleware[] = $middleware;
        }

        return $this;
    }

    public function addPostware(ControllerInterface|Closure ...$postwares): self
    {
        foreach ($postwares as $postware) {
            if ($postware instanceof Closure) {
                $postware = new ClosureRequestHandler($postware);
            }

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

    public function acceptsMethod(string $method): bool
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
