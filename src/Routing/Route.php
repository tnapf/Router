<?php

namespace Tnapf\Router\Routing;

use InvalidArgumentException;
use stdClass;
use Tnapf\Router\Enums\Methods;
use Tnapf\Router\Router;
use Tnapf\Router\Interfaces\RequestHandlerInterface;

class Route
{
    public readonly string $uri;
    private array $methods;

    /**
     * @var RequestHandlerInterface[]
     */
    private array $middleware = [];

    /**
     * @var RequestHandlerInterface[]
     */
    private array $postware = [];
    private stdClass $parameters;

    /**
     * @param string                                $uri
     * @param class-string<RequestHandlerInterface> $controller
     * @param Methods                               ...$methods
     */
    public function __construct(string $uri, public readonly string $controller, Methods ...$methods)
    {
        if (!str_starts_with($uri, "/") && empty(Router::getBaseUri())) {
            $uri = "/{$uri}";
        } else if (!empty(Router::getBaseUri()) && str_ends_with($uri, "/")) {
            $uri = substr($uri, 0, -1);
        }

        if (!is_subclass_of($controller, RequestHandlerInterface::class)) {
            throw new InvalidArgumentException("{$controller} must implement " . RequestHandlerInterface::class);
        }

        $this->uri = Router::getBaseUri() . "$uri";

        $this->parameters = new stdClass();

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

    /**
     * @param  class-string<RequestHandlerInterface> ...$middlewares
     * @return self
     */
    public function addMiddleware(string ...$middlewares): self
    {
        foreach ($middlewares as $middleware) {
            if (!is_subclass_of($middleware, RequestHandlerInterface::class)) {
                throw new InvalidArgumentException("{$middleware} must implement " . RequestHandlerInterface::class);
            }

            $this->middleware[] = $middleware;
        }

        return $this;
    }

    /**
     * @param  class-string<RequestHandlerInterface> ...$postwares
     * @return self
     */
    public function addPostware(string ...$postwares): self
    {
        foreach ($postwares as $postware) {
            if (!is_subclass_of($postware, RequestHandlerInterface::class)) {
                throw new InvalidArgumentException("{$postware} must implement " . RequestHandlerInterface::class);
            }

            $this->postware[] = $postware;
        }

        return $this;
    }

    /**
     * @return RequestHandlerInterface[]
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * @return RequestHandlerInterface[]
     */
    public function getPostware(): array
    {
        return $this->postware;
    }

    public function acceptsMethod(Methods $method): bool
    {
        return in_array($method, $this->methods);
    }
}
