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
use stdClass;
use Throwable;
use Tnapf\Router\Enums\Methods;
use Tnapf\Router\Exceptions\HttpException;
use Tnapf\Router\Exceptions\HttpInternalServerError;
use Tnapf\Router\Interfaces\RequestHandlerInterface;
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
    protected static array $routes = [];

    protected static array $group = [];

    protected static ?EmitterInterface $emitter = null;

    /**
     * @var int The type of emit
     */
    protected static int $emitHttpExceptions = 0;

    /**
     * @var Route[][]
     */
    protected static array $catchers = [];

    /**
     * @param  string                                $uri
     * @param  class-string<RequestHandlerInterface> $controller
     * @return Route
     */
    public static function get(string $uri, string $controller): Route
    {
        $route = new Route($uri, $controller, Methods::GET);

        self::addRoute($route);

        return $route;
    }

    /**
     * @param  string                                $uri
     * @param  class-string<RequestHandlerInterface> $controller
     * @return Route
     */
    public static function post(string $uri, string $controller): Route
    {
        $route = new Route($uri, $controller, Methods::POST);

        self::addRoute($route);

        return $route;
    }

    /**
     * @param  string                                $uri
     * @param  class-string<RequestHandlerInterface> $controller
     * @return Route
     */
    public static function put(string $uri, string $controller): Route
    {
        $route = new Route($uri, $controller, Methods::PUT);

        self::addRoute($route);

        return $route;
    }

    /**
     * @param  string                                $uri
     * @param  class-string<RequestHandlerInterface> $controller
     * @return Route
     */
    public static function delete(string $uri, string $controller): Route
    {
        $route = new Route($uri, $controller, Methods::DELETE);

        self::addRoute($route);

        return $route;
    }

    /**
     * @param  string                                $uri
     * @param  class-string<RequestHandlerInterface> $controller
     * @return Route
     */
    public static function options(string $uri, string $controller): Route
    {
        $route = new Route($uri, $controller, Methods::OPTIONS);

        self::addRoute($route);

        return $route;
    }

    /**
     * @param  string                                $uri
     * @param  class-string<RequestHandlerInterface> $controller
     * @return Route
     */
    public static function head(string $uri, string $controller): Route
    {
        $route = new Route($uri, $controller, Methods::HEAD);

        self::addRoute($route);

        return $route;
    }

    /**
     * @param  string                                $uri
     * @param  class-string<RequestHandlerInterface> $controller
     * @return Route
     */
    public static function all(string $uri, string $controller): Route
    {
        $route = new Route($uri, $controller, ...Methods::cases());

        self::addRoute($route);

        return $route;
    }

    /**
     * @param  Route $route
     * @return void
     */
    public static function addRoute(Route $route): void
    {
        if (isset(self::$group)) {
            foreach (self::$group["middlewares"] ?? [] as $middlware) {
                $route->addMiddleware($middlware);
            }

            foreach (self::$group["postwares"] ?? [] as $postware) {
                $route->addPostware($postware);
            }

            foreach (self::$group["parameters"] ?? [] as $name => $value) {
                $route->setParameter($name, $value);
            }

            foreach (self::$group["staticArguments"] ?? [] as $name => $value) {
                $route->addStaticArgument($name, $value);
            }
        }

        self::$routes[] = &$route;
    }

    public static function group(
        string $baseUri,
        Closure $grouping,
        array $middlewares = [],
        array $postwares = [],
        array $parameters = [],
        array $staticArguments = []
    ): void {
        $oldMount = self::$group;

        if (empty(self::getBaseUri())) {
            self::$group = compact("baseUri", "middlewares", "postwares", "parameters", "staticArguments");
        } else {
            self::$group['baseUri'] .= $baseUri;
            self::$group['middlewares'] = [...self::$group['middlewares'], ...$middlewares];
            self::$group['postwares'] = [...self::$group['postwares'], ...$postwares];
            self::$group['parameters'] = [...self::$group['parameters'], ...$parameters];
            self::$group['staticArguments'] = [...self::$group['staticArguments'], ...$staticArguments];
        }

        $grouping();

        self::$group = $oldMount;
    }

    public static function getBaseUri(): string
    {
        return self::$group["baseUri"] ?? "";
    }

    /**
     * @param  array $routes
     * @return ResolvedRoute|null
     */
    protected static function resolveRoute(array $routes, ServerRequestInterface $request): ?ResolvedRoute
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


    /**
     * @param string $toCatch
     * @param class-string $controller
     * @param string|null $uri
     * @return Route
     */
    public static function catch(string $toCatch, string $controller, ?string $uri = "/(.*)"): Route
    {
        $catchable = array_keys(self::$catchers);

        if (!in_array($toCatch, $catchable)) {
            self::makeCatchable($toCatch);
        }

        $route = new Route($uri, $controller, ...Methods::cases());

        self::$catchers[$toCatch][] = &$route;

        return $route;
    }

    /**
     * @param  class-string<HttpException> $toCatch
     * @return void
     */
    public static function makeCatchable(string $toCatch): void
    {
        if (isset(self::$catchers[$toCatch])) {
            return;
        }

        if (!is_subclass_of($toCatch, HttpException::class)) {
            throw new InvalidArgumentException("{$toCatch} must extend " . HttpException::class);
        }

        self::$catchers[$toCatch] = [];
    }

    /**
     * @return Route[]
     */
    public static function getRoutes(bool $sort = true): array
    {
        if ($sort) {
            self::sortRoutesAndCatchers();
        }

        return self::$routes;
    }

    /**
     * @return Route[][]
     */
    public static function getCatchers(bool $sort = true): array
    {
        if ($sort) {
            self::sortRoutesAndCatchers();
        }

        return self::$catchers;
    }

    public static function emitHttpExceptions(int $type): void
    {
        self::$emitHttpExceptions = $type;
    }

    protected static function invokeRoute(
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

    protected static function sortRoutesAndCatchers(): void
    {
        $sortByLength = static function (Route $a, Route $b) {
            return (strlen($a->uri) > strlen($b->uri));
        };

        foreach (self::$catchers as &$catcher) {
            usort($catcher, $sortByLength);
        }

        unset($catcher);

        usort(self::$routes, $sortByLength);
    }

    public static function run(?ServerRequestInterface $request = null, ?EmitterInterface $emitter = null): void
    {
        self::$emitter = $emitter ?? new SapiEmitter();
        $routes = self::getRoutes();
        $catchers = self::getCatchers(false);
        $request ??= ServerRequestCreator::createFromGlobals();
        $resolved = self::resolveRoute($routes, $request);

        try {
            if ($resolved === null) {
                throw new Exceptions\HttpNotFound($request);
            }

            $response = self::invokeRoute($resolved, $request);
        } catch (Throwable $e) {
            if (array_key_exists($e::class, $catchers)) {
                $resolved = self::resolveRoute($catchers[$e::class], $request);
            } else {
                $resolved = self::resolveRoute($catchers[HttpInternalServerError::class] ?? [], $request);
            }

            if ($resolved === null) {
                if (!is_subclass_of($e, HttpException::class)) {
                    throw $e;
                }

                $class = $e::class;

                $method = match (self::$emitHttpExceptions) {
                    self::EMIT_HTML_RESPONSE => "buildHtmlResponse",
                    self::EMIT_JSON_RESPONSE => "buildJsonResponse",
                    default => "buildEmptyResponse"
                };

                $response = call_user_func("{$class}::{$method}");
            }

            if (!isset($response)) {
                $resolved->args->exception = $e;
                $response = self::invokeRoute($resolved, $request);
            }
        }

        self::$emitter->emit($response);
    }
}
