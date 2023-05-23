<?php

namespace Tnapf\Router\Routing;

use HttpSoft\Message\Response;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use Throwable;
use Tnapf\Router\Interfaces\ControllerInterface;

class RouteRunner
{
    protected array $controllersToRun = [];

    protected bool $isRunning = false;

    public stdClass $args;
    public Throwable $exception;

    public function __construct()
    {
        $this->args = new stdClass();
    }

    public function run(ServerRequestInterface $request, ?ResponseInterface $response = null): ResponseInterface
    {
        $this->throwIfRunning();

        $this->isRunning = true;

        $response = $this->next($request, $response ?? new Response());

        $this->isRunning = false;

        return $response;
    }

    public function next(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $controller = array_shift($this->controllersToRun);

        if ($controller === null) {
            return $response;
        }

        return $controller->handle($request, $response, $this);
    }

    protected function throwIfRunning(): void
    {
        if ($this->isRunning) {
            throw new LogicException("Route is already running!");
        }
    }

    public function appendControllersToRun(ControllerInterface ...$controllers): void
    {
        $this->throwIfRunning();

        foreach ($controllers as $controller) {
            $this->controllersToRun[] = $controller;
        }
    }

    public static function createFromRoute(Route $route, ?stdClass $args = null): self
    {
        $runner = new self();

        $runner->controllersToRun = [...$route->getMiddleware(), $route->controller, ...$route->getPostware()];

        if ($args !== null) {
            $runner->args = $args;
        }

        foreach ($route->getStaticArguments() as $name => $value) {
            $runner->args->$name = $value;
        }

        return $runner;
    }

    public function getParameter(string $name): mixed
    {
        return $this->args->$name ?? null;
    }
}
