<?php

namespace Tnapf\Router;

use HttpSoft\ServerRequest\ServerRequestCreator;
use Tnapf\Router\Exceptions\HttpNotFound;
use Tnapf\Router\Interfaces\ControllerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use HttpSoft\Emitter\EmitterInterface;
use Tnapf\Router\Routing\RouteRunner;
use HttpSoft\Emitter\SapiEmitter;
use Tnapf\Router\Routing\Methods;
use Tnapf\Router\Routing\Route;
use HttpSoft\Message\Response;
use Closure;
use stdClass;
use Throwable;

use function array_keys;
use function in_array;
use function strtoupper;

class Router
{
    /**
     * @var Route[]
     */
    protected array $routes = [];
    protected array $group = [];

    /**
     * @var Route[][]
     */
    protected array $catchers = [];
    protected bool $sorted = false;

    public function __construct()
    {
        $this->catchers[Throwable::class] = [];
    }

    public function get(string $uri, ControllerInterface|Closure $controller): Route
    {
        return $this->addRoute(Route::new($uri, $controller, $this->getBaseUri(), Methods::GET));
    }

    public function post(string $uri, ControllerInterface|Closure $controller): Route
    {
        return $this->addRoute(Route::new($uri, $controller, $this->getBaseUri(), Methods::POST));
    }

    public function put(string $uri, ControllerInterface|Closure $controller): Route
    {
        return $this->addRoute(Route::new($uri, $controller, $this->getBaseUri(), Methods::PUT));
    }

    public function patch(string $uri, ControllerInterface|Closure $controller): Route
    {
        return $this->addRoute(Route::new($uri, $controller, $this->getBaseUri(), Methods::PATCH));
    }

    public function delete(string $uri, ControllerInterface|Closure $controller): Route
    {
        return $this->addRoute(Route::new($uri, $controller, $this->getBaseUri(), Methods::DELETE));
    }


    public function options(string $uri, ControllerInterface|Closure $controller): Route
    {
        return $this->addRoute(Route::new($uri, $controller, $this->getBaseUri(), Methods::OPTIONS));
    }


    public function head(string $uri, ControllerInterface|Closure $controller): Route
    {
        return $this->addRoute(Route::new($uri, $controller, $this->getBaseUri(), Methods::HEAD));
    }


    public function all(string $uri, ControllerInterface|Closure $controller): Route
    {
        return $this->addRoute(Route::new($uri, $controller, $this->getBaseUri(), ...Methods::ALL));
    }


    public function addRoute(Route $route): Route
    {
        if (isset($this->group)) {
            foreach ($this->group["middlewares"] ?? [] as $middlware) {
                $route->addMiddleware($middlware);
            }

            foreach ($this->group["postwares"] ?? [] as $postware) {
                $route->addPostware($postware);
            }

            foreach ($this->group["parameters"] ?? [] as $name => $value) {
                $route->setParameter($name, $value);
            }

            foreach ($this->group["staticArguments"] ?? [] as $name => $value) {
                $route->addStaticArgument($name, $value);
            }
        }

        $this->routes[] = &$route;

        $this->sorted = false;

        return $route;
    }

    public function group(
        string $baseUri,
        Closure $grouping,
        array $middlewares = [],
        array $postwares = [],
        array $parameters = [],
        array $staticArguments = []
    ): self {
        $oldMount = $this->group;

        if (empty($this->getBaseUri())) {
            $this->group = compact("baseUri", "middlewares", "postwares", "parameters", "staticArguments");
        } else {
            $this->group['baseUri'] .= $baseUri;
            $this->group['middlewares'] = [...$this->group['middlewares'], ...$middlewares];
            $this->group['postwares'] = [...$this->group['postwares'], ...$postwares];
            $this->group['parameters'] = [...$this->group['parameters'], ...$parameters];
            $this->group['staticArguments'] = [...$this->group['staticArguments'], ...$staticArguments];
        }

        $grouping($this);

        $this->group = $oldMount;

        return $this;
    }

    public function getBaseUri(): string
    {
        return $this->group["baseUri"] ?? "";
    }

    protected function resolveRoute(array $routes, ServerRequestInterface $request): ?RouteRunner
    {
        $routeMatches = static function (Route $route, string $requestUri, ?array &$matches) use (&$argNames): bool {
            $argNames = [];

            $routeParts = explode("/", $route->uri);

            if (!str_ends_with($route->uri, "/(.*)") && count(explode("/", $requestUri)) !== count($routeParts)) {
                return false;
            }

            foreach ($routeParts as $key => $part) {
                if (str_starts_with($part, "{") && str_ends_with($part, "}")) {
                    $name = str_replace(["{", "}"], "", $part);
                    $part = $route->getParameter($name);

                    $argNames[] = $name;
                }

                $routeParts[$key] = $part;
            }

            $uri = implode("/", $routeParts);

            $pattern = preg_replace('/\/{(.*?)}/', '/(.*?)', $uri);

            return (bool)preg_match_all('#^' . $pattern . '$#', $requestUri, $matches, PREG_OFFSET_CAPTURE);
        };

        $uri = $request->getUri()->getPath();

        $method = strtoupper($request->getMethod());

        foreach ($routes as $route) {
            $matches = [];
            $isMatch = $routeMatches($route, $uri, $matches);

            if (!$isMatch || !$route->acceptsMethod($method)) {
                continue;
            }

            $args = new stdClass();
            $argsIterator = 0;

            foreach ($matches as $index => $match) {
                if (!$index) {
                    continue;
                }

                $name = $argNames[$argsIterator++] ?? "";

                $args->$name = isset($match[0][0]) && $match[0][1] != -1 ? trim($match[0][0], '/') : null;
            }

            $runner = RouteRunner::createFromRoute($route, $args);
        }

        return $runner ?? null;
    }

    public function catch(string $toCatch, ControllerInterface|Closure $controller, ?string $uri = "/(.*)"): Route
    {
        $catchable = array_keys($this->catchers);

        if (!in_array($toCatch, $catchable)) {
            $this->catchers[$toCatch] = [];
        }

        $route = new Route($uri, $controller, $this->getBaseUri(), ...Methods::ALL);

        $this->catchers[$toCatch][] = &$route;

        $this->sorted = false;

        return $route;
    }

    /**
     * @return Route[]
     */
    public function getRoutes(bool $sort = true): array
    {
        if ($sort) {
            $this->sortRoutesAndCatchers();
        }

        return $this->routes;
    }

    /**
     * @return Route[][]
     */
    public function getCatchers(bool $sort = true): array
    {
        if ($sort) {
            $this->sortRoutesAndCatchers();
        }

        return $this->catchers;
    }

    protected function sortRoutesAndCatchers(): void
    {
        if ($this->sorted) {
            return;
        }

        $sortByLength = static fn(Route $a, Route $b) => (strlen($a->uri) <=> strlen($b->uri));

        array_walk($this->catchers, static fn(array &$catcher) => usort($catcher, $sortByLength));

        usort($this->routes, $sortByLength);

        $this->sorted = true;
    }

    public function clearRoutes(): void
    {
        $this->routes = [];
    }

    public function clearCatchers(): void
    {
        $this->catchers = [Throwable::class => []];
    }

    public function clearAll(): void
    {
        $this->clearRoutes();
        $this->clearCatchers();
    }

    protected function invokeRoute(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ?RouteRunner $runner = null,
        bool $catching = false
    ): ResponseInterface {
        try {
            if ($runner === null) {
                throw new HttpNotFound($request);
            }

            return $runner->run($request);
        } catch (Throwable $e) {
            $catchers = $this->getCatchers();
            $exceptionCatchers = $catchers[$e::class] ?? $catchers[Throwable::class];
            $runner = $this->resolveRoute($exceptionCatchers, $request);

            if ($runner === null || $catching) {
                throw $e;
            }

            $runner->exception = $e;
            return $this->invokeRoute($request, $response, $runner, true);
        }
    }

    public function run(?ServerRequestInterface $request = null): ResponseInterface
    {
        $routes = $this->getRoutes();
        $request ??= ServerRequestCreator::createFromGlobals();
        $runner = $this->resolveRoute($routes, $request);

        return $this->invokeRoute($request, new Response(), $runner);
    }

    public function emit(?ServerRequestInterface $request = null, ?EmitterInterface $emitter = null): void
    {
        $response = $this->run($request);
        $emitter ??= new SapiEmitter();

        $emitter->emit($response);
    }
}
