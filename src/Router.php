<?php

namespace Tnapf\Router;

use Closure;
use HttpSoft\Emitter\EmitterInterface;
use HttpSoft\Emitter\SapiEmitter;
use HttpSoft\Message\Response;
use HttpSoft\ServerRequest\ServerRequestCreator;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use stdClass;
use Throwable;
use Tnapf\Router\Enums\Methods;
use Tnapf\Router\Exceptions\HttpException;
use Tnapf\Router\Exceptions\HttpInternalServerError;
use Tnapf\Router\Routing\Route;
use Tnapf\Router\Routing\ResolvedRoute;

class Router
{
    public const EMIT_EMPTY_RESPONSE = 1;
    public const EMIT_HTML_RESPONSE = 2;
    public const EMIT_JSON_RESPONSE = 3;

    /**
     * @var Route[]
     */
    protected array $routes = [];
    protected array $group = [];
    protected int $emitHttpExceptions = 0;

    /**
     * @var Route[][]
     */
    protected array $catchers = [];

    public function get(string $uri, RequestHandlerInterface $controller): Route
    {
        $route = new Route($this, $uri, $controller, Methods::GET);

        $this->addRoute($route);

        return $route;
    }

    public function post(string $uri, RequestHandlerInterface $controller): Route
    {
        $route = new Route($this, $uri, $controller, Methods::POST);

        $this->addRoute($route);

        return $route;
    }

    public function put(string $uri, RequestHandlerInterface $controller): Route
    {
        $route = new Route($this, $uri, $controller, Methods::PUT);

        $this->addRoute($route);

        return $route;
    }

    public function patch(string $uri, RequestHandlerInterface $controller): Route
    {
        $route = new Route($this, $uri, $controller, Methods::PATCH);

        $this->addRoute($route);

        return $route;
    }

    public function delete(string $uri, RequestHandlerInterface $controller): Route
    {
        $route = new Route($this, $uri, $controller, Methods::DELETE);

        $this->addRoute($route);

        return $route;
    }


    public function options(string $uri, RequestHandlerInterface $controller): Route
    {
        $route = new Route($this, $uri, $controller, Methods::OPTIONS);

        $this->addRoute($route);

        return $route;
    }


    public function head(string $uri, RequestHandlerInterface $controller): Route
    {
        $route = new Route($this, $uri, $controller, Methods::HEAD);

        $this->addRoute($route);

        return $route;
    }


    public function all(string $uri, RequestHandlerInterface $controller): Route
    {
        $route = new Route($this, $uri, $controller, ...Methods::cases());

        $this->addRoute($route);

        return $route;
    }


    public function addRoute(Route $route): void
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
    }

    public function group(
        string $baseUri,
        Closure $grouping,
        array $middlewares = [],
        array $postwares = [],
        array $parameters = [],
        array $staticArguments = []
    ): void {
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

        $grouping();

        $this->group = $oldMount;
    }

    public function getBaseUri(): string
    {
        return $this->group["baseUri"] ?? "";
    }

    /**
     * @param array $routes
     * @param ServerRequestInterface $request
     * @return ResolvedRoute|null
     */
    protected function resolveRoute(array $routes, ServerRequestInterface $request): ?ResolvedRoute
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

            return (bool) preg_match_all('#^' . $pattern . '$#', $requestUri, $matches, PREG_OFFSET_CAPTURE);
        };

        $uri = explode("?", $request->getRequestTarget())[0];

        $method = Methods::from($request->getMethod());

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

            $resolvedRoute = new ResolvedRoute($route, $args);
        }

        return $resolvedRoute ?? null;
    }

    public function catch(string $toCatch, string $controller, ?string $uri = "/(.*)"): Route
    {
        $catchable = array_keys($this->catchers);

        if (!in_array($toCatch, $catchable)) {
            $this->makeCatchable($toCatch);
        }

        $route = new Route($this, $uri, $controller, ...Methods::cases());

        $this->catchers[$toCatch][] = &$route;

        return $route;
    }

    public function makeCatchable(string $toCatch): void
    {
        if (!isset($this->catchers[$toCatch])) {
            if (!is_subclass_of($toCatch, HttpException::class)) {
                throw new InvalidArgumentException("{$toCatch} must extend " . HttpException::class);
            }

            $this->catchers[$toCatch] = [];
        }
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

    public function emitHttpExceptions(int $type): void
    {
        $this->emitHttpExceptions = $type;
    }

    protected function invokeRoute(
        ResolvedRoute $resolvedRoute,
        ServerRequestInterface $request
    ): ResponseInterface {
        $response = new Response();

        $controllers = [
            ...$resolvedRoute->route->getMiddleware(),
            $resolvedRoute->route->controller,
            ...$resolvedRoute->route->getPostware()
        ];

        $next = static function (
            ServerRequestInterface $request,
            ResponseInterface $response,
            stdClass $args
        ) use (
            &$controllers,
            &$next
        ): ResponseInterface {
            $controller = array_shift($controllers);

            if ($controller === null) {
                return $response;
            }

            return call_user_func("{$controller}::handle", $request, $response, $args, $next);
        };

        foreach ($resolvedRoute->route->getStaticArguments() as $name => $value) {
            $resolvedRoute->args->$name = $value;
        }

        return $next($request, $response, $resolvedRoute->args);
    }

    protected function sortRoutesAndCatchers(): void
    {
        $sortByLength = static function (Route $a, Route $b) {
            return (strlen($a->uri) > strlen($b->uri));
        };

        foreach ($this->catchers as &$catcher) {
            usort($catcher, $sortByLength);
        }

        unset($catcher);

        usort($this->routes, $sortByLength);
    }

    public function clearRoutes(): void
    {
        $this->routes = [];
    }

    public function clearCatchers(): void
    {
        $this->catchers = [];
    }

    public function clearAll(): void
    {
        $this->clearRoutes();
        $this->clearCatchers();
    }

    public function run(?ServerRequestInterface $request = null): ResponseInterface
    {
        $routes = $this->getRoutes();
        $catchers = $this->getCatchers(false);
        $request ??= ServerRequestCreator::createFromGlobals();
        $resolved = $this->resolveRoute($routes, $request);

        try {
            if ($resolved === null) {
                throw new Exceptions\HttpNotFound($request);
            }

            $response = $this->invokeRoute($resolved, $request);
        } catch (Throwable $e) {
            $resolved = array_key_exists($e::class, $catchers) ?
                $this->resolveRoute($catchers[$e::class], $request) :
                $this->resolveRoute($catchers[HttpInternalServerError::class] ?? [], $request)
            ;

            if ($resolved === null) {
                if (!$e instanceof HttpException) {
                    throw $e;
                }

                $class = $e::class;

                $method = match ($this->emitHttpExceptions) {
                    self::EMIT_HTML_RESPONSE => "buildHtmlResponse",
                    self::EMIT_JSON_RESPONSE => "buildJsonResponse",
                    default => "buildEmptyResponse"
                };

                $response = call_user_func("{$class}::{$method}");
            }

            if (!isset($response)) {
                $resolved->args->exception = $e;
                $response = $this->invokeRoute($resolved, $request);
            }
        }

        return $response;
    }
}
