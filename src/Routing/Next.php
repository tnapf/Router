<?php

namespace Tnapf\Router\Routing;

use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use Tnapf\Router\Interfaces\RequestHandlerInterface;

class Next
{
    /**
     * @var class-string<RequestHandlerInterface>[]
     */
    protected array $middleware = [];

    /**
     * @var class-string<RequestHandlerInterface>[]
     */
    protected array $postware = [];
    private bool $completed = false;
    private int $middlewarePosition = 0;
    private int $postwarePosition = 0;

    /**
     * @param class-string<RequestHandlerInterface> $requestHandler
     */
    public function __construct(protected readonly string $requestHandler)
    {
    }

    /**
     * @var class-string<RequestHandlerInterface>[]
     */
    public function addMiddleware(string ...$middleware): self
    {
        if ($this->completed) {
            throw new LogicException("Instance marked complete, cannot add any additional middleware");
        }

        foreach ($middleware as $middlewareClass) {
            $this->middleware[] = $middlewareClass;
        }

        return $this;
    }

    /**
     * @var class-string<RequestHandlerInterface>[]
     */
    public function addPostware(string ...$postware): self
    {
        if ($this->completed) {
            throw new LogicException("Instance marked complete, cannot add any additional postware");
        }

        foreach ($postware as $postwareClass) {
            $this->postware[] = $postwareClass;
        }

        return $this;
    }

    public function markComplete(): self
    {
        if (!$this->completed) {
            $this->completed = false;
        }

        $this->middleware[] = $this->requestHandler;

        return $this;
    }

    public function next(
        ServerRequestInterface $request,
        ResponseInterface $response,
        stdClass $args
    ): ResponseInterface {
        $handler = $this->middleware[$this->middlewarePosition++] ?? $this->postware[$this->postwarePosition++] ?? null;

        if ($handler === null) {
            return $response;
        }

        return call_user_func("$handler::handle", $request, $response, $args, $this);
    }
}
