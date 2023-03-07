<?php

namespace Tnapf\Router;

use Closure;
use HttpSoft\Emitter\EmitterInterface;
use HttpSoft\Emitter\SapiEmitter;
use HttpSoft\Runner\MiddlewarePipeline;
use HttpSoft\Runner\MiddlewareResolver;
use HttpSoft\Runner\ServerRequestRunner;
use HttpSoft\ServerRequest\ServerRequestCreator;
use stdClass;
use Throwable;
use Tnapf\Router\Enums\Methods;
use Tnapf\Router\Exceptions\HttpException;
use Tnapf\Router\Exceptions\HttpInternalServerError;
use Tnapf\Router\Routing\Route;
use Psr\Http\Server\RequestHandlerInterface;

final class Router {
    public const EMIT_EMPTY_RESPONSE = 1;
    public const EMIT_HTML_RESPONSE = 2;
    public const EMIT_JSON_RESPONSE = 3;

    /**
     * @var Route[]
     */
    protected static array $routes = [];

    protected static array $mount = [];

    protected static ?EmitterInterface $emitter = null;

    /**
     * @var int The type of emit
     */
    protected static int $emitHttpExceptions = 0;

    /**
     * @var Route[][]
     */
    protected static array $catchers = [
        Exceptions\HttpBadRequest::class => [],
        Exceptions\HttpUnauthorized::class => [],
        Exceptions\HttpPaymentRequired::class => [],
        Exceptions\HttpForbidden::class => [],
        Exceptions\HttpNotFound::class => [],
        Exceptions\HttpMethodNotAllowed::class => [],
        Exceptions\HttpNotAcceptable::class => [],
        Exceptions\HttpProxyAuthenticationRequired::class => [],
        Exceptions\HttpRequestTimeout::class => [],
        Exceptions\HttpConflict::class => [],
        Exceptions\HttpGone::class => [],
        Exceptions\HttpLengthRequired::class => [],
        Exceptions\HttpPreconditionFailed::class => [],
        Exceptions\HttpPayloadTooLarge::class => [],
        Exceptions\HttpURITooLong::class => [],
        Exceptions\HttpUnsupportedMediaType::class => [],
        Exceptions\HttpRangeNotSatisfiable::class => [],
        Exceptions\HttpExpectationFailed::class => [],
        Exceptions\HttpImateapot::class => [],
        Exceptions\HttpUpgradeRequired::class => [],
        Exceptions\HttpUnprocessableEntity::class => [],
        Exceptions\HttpLocked::class => [],
        Exceptions\HttpFailedDependency::class => [],
        Exceptions\HttpPreconditionRequired::class => [],
        Exceptions\HttpTooManyRequests::class => [],
        Exceptions\HttpRequestHeaderFieldsTooLarge::class => [],
        Exceptions\HttpUnavailableForLegalReasons::class => [],
        Exceptions\HttpInternalServerError::class => [],
        Exceptions\HttpNotImplemented::class => [],
        Exceptions\HttpBadGateway::class => [],
        Exceptions\HttpServiceUnavailable::class => [],
        Exceptions\HttpGatewayTimeout::class => [],
        Exceptions\HttpHTTPVersionNotSupported::class => [],
        Exceptions\HttpVariantAlsoNegotiates::class => [],
        Exceptions\HttpInsufficientStorage::class => [],
        Exceptions\HttpNetworkAuthenticationRequired::class => []
    ];

    /**
     * @param string $uri
     * @param RequestHandlerInterface $controller
     * @return Route
     */
    public static function get(string $uri, RequestHandlerInterface $controller): Route
    {
        $route = new Route($uri, $controller, Methods::GET);
        
        self::addRoute($route);
        
        return $route;
    }

    /**
     * @param string $uri
     * @param RequestHandlerInterface $controller
     * @return Route
     */
    public static function post(string $uri, RequestHandlerInterface $controller): Route
    {
        $route = new Route($uri, $controller, Methods::POST);
        
        self::addRoute($route);

        return $route;
    }

    /**
     * @param string $uri
     * @param RequestHandlerInterface $controller
     * @return Route
     */
    public static function put(string $uri, RequestHandlerInterface $controller): Route
    {
        $route = new Route($uri, $controller, Methods::PUT);
        
        self::addRoute($route);

        return $route;
    }

    /**
     * @param string $uri
     * @param RequestHandlerInterface $controller
     * @return Route
     */
    public static function delete(string $uri, RequestHandlerInterface $controller): Route
    {
        $route = new Route($uri, $controller, Methods::DELETE);
        
        self::addRoute($route);

        return $route;
    }

    /**
     * @param string $uri
     * @param RequestHandlerInterface $controller
     * @return Route
     */
    public static function options(string $uri, RequestHandlerInterface $controller): Route
    {
        $route = new Route($uri, $controller, Methods::OPTIONS);
        
        self::addRoute($route);

        return $route;
    }

    /**
     * @param string $uri
     * @param RequestHandlerInterface $controller
     * @return Route
     */
    public static function head(string $uri, RequestHandlerInterface $controller): Route
    {
        $route = new Route($uri, $controller, Methods::HEAD);
        
        self::addRoute($route);

        return $route;
    }

    /**
     * @param string $uri
     * @param RequestHandlerInterface $controller
     * @return Route
     */
    public static function all(string $uri, RequestHandlerInterface $controller): Route
    {
        $route = new Route($uri, $controller, ...Methods::cases());
        
        self::addRoute($route);

        return $route;
    }

    /**
     * @param Route $route
     * @return void
     */
    public static function addRoute(Route &$route): void
    {
        if (isset(self::$mount)) {
            foreach (self::$mount["middleware"] ?? [] as $before) {
                $route->before($before);
            }
            
            foreach (self::$mount["postware"] ?? [] as $after) {
                $route->after($after);
            }
        }

        self::$routes[$route->uri] = &$route;
    }

    public static function group(string $baseUri, Closure $grouping, array $middleware = [], array $postware = []): void
    {
        $oldMount = self::$mount;

        if (empty(self::$mount['baseUri'])) {
            self::$mount = compact("baseUri", "middleware", "postware");
        } else {
            self::$mount['baseUri'] .= $baseUri;
            self::$mount['middleware'] = array_merge(self::$mount['middleware'], $middleware);
            self::$mount['postware'] = array_merge(self::$mount['postware'], $postware);
        }

        $grouping();

        self::$mount = $oldMount;
    }

    public static function getBaseUri(): string
    {
        return self::$mount["baseUri"] ?? "";
    }

    /**
     * @param array $routes
     * @return stdClass|null
     */
    private static function resolveRoute(array $routes): ?stdClass
    {
        $routeMatches = static function (Route $route, string $requestUri, array|null &$matches) use (&$argNames): bool
        {
            $argNames = [];

            $routeParts = explode("/", $route->uri);    

            if (count(explode("/", $requestUri)) !== count($routeParts) && !str_ends_with($route->uri, "/(.*)")) {
                return false;
            }

            foreach ($routeParts as $key => $part) {
                if (substr($part, 0, 1) === "{" && substr($part, -1) === "}") {
                    $name = str_replace(["{", "}"], "", $part);
                    $part = $route->getParameter($name);

                    $argNames[] = $name;
                }
                
                $routeParts[$key] = $part;
            }

            $uri = implode("/", $routeParts);

            $pattern = preg_replace('/\/{(.*?)}/', '/(.*?)', $uri);

            return boolval(preg_match_all('#^' . $pattern . '$#', $requestUri, $matches, PREG_OFFSET_CAPTURE));
        };

        $uri = explode("?", $_SERVER["REQUEST_URI"])[0];

        $method = Methods::from($_SERVER["REQUEST_METHOD"]);

        foreach ($routes as $route) {
            $matches = [];

            $isMatch = $routeMatches($route, $uri, $matches);

            if (!$isMatch || !$route->acceptsMethod($method)) {
                continue;
            }

            $matchedRoute = new stdClass;

            $args = new stdClass;

            $argsIterator = 0;
            foreach ($matches as $index => $match) {
                if (!$index) {
                    continue;
                }

                $name = $argNames[$argsIterator++] ?? "";

                $args->$name = isset($match[0][0]) && $match[0][1] != -1 ? trim($match[0][0], '/') : null;
            }

            $matchedRoute->route = $route;
            $matchedRoute->args = $args;
        }

        return $matchedRoute ?? null;
    }

    /**
     * @param class-string<Throwable> $exceptionToCatch
     * @param RequestHandlerInterface $controller
     */
    public static function catch(string $toCatch, RequestHandlerInterface $controller, ?string $uri = "/(.*)"): Route
    {
        $catchable = array_keys(self::$catchers);

        if (!in_array($toCatch, $catchable)) {
            throw new \InvalidArgumentException("You can only catch the following exceptions: ".implode(", ", $catchable));
        }
        
        $route = new Route($uri, $controller, ...Methods::cases());
        
        self::$catchers[$toCatch][] = &$route;

        return $route;
    }


    public static function makeCatchable(string $toCatch): void
    {
        if (isset(self::$catchers[$toCatch])) {
            return;
        }

        self::$catchers[$toCatch] = [];
    }

    /**
     * 
     */
    public static function emitHttpExceptions(int $type): void
    {
        self::$emitHttpExceptions = $type;
    }

    public static function invokeRoute(Route $route): void
    {
        $request = ServerRequestCreator::createFromGlobals($_SERVER);
        $pipeline = new MiddlewarePipeline();
        $runner = new ServerRequestRunner($pipeline, self::$emitter);
        $resolver = new MiddlewareResolver();

        foreach ($route->getMiddleware() as $middleware) {
            $pipeline->pipe($middleware);
        }

        $pipeline->pipe($resolver->resolve(fn() => $route->controller->handle($request)));

        foreach ($route->getPostware() as $postware) {
            $pipeline->pipe($postware);
        }

        $runner->run($request);
    }

    public static function run(EmitterInterface $emitter = null): void
    {
        self::$emitter = $emitter;

        $sortByLength = function (Route $a, Route $b) {
            return (strlen($a->uri) > strlen($b->uri));
        };

        foreach (self::$catchers as &$catcher) {
            usort($catcher, $sortByLength);
        }

        usort(self::$routes, $sortByLength);

        $resolved = self::resolveRoute(self::$routes);

        $request = ServerRequestCreator::createFromGlobals($_SERVER);

        try {
            if ($resolved === null) {
                throw new Exceptions\HttpNotFound($request);
            }

            self::invokeRoute($resolved->route);
        } catch (Throwable $e) {
            if (in_array($e::class, array_keys(self::$catchers))) {
                $resolved = self::resolveRoute(self::$catchers[$e::class]);
            } else {
                $resolved = self::resolveRoute(self::$catchers[HttpInternalServerError::class]);
            }

            if ($resolved === null) {
                if (!self::$emitHttpExceptions) {
                    throw $e;
                } else {
                    if (!is_subclass_of($e, HttpException::class)) {
                        $class = HttpInternalServerError::class;
                    } else {
                        $class = $e::class;
                    }

                    $method = match(self::$emitHttpExceptions) {
                        self::EMIT_EMPTY_RESPONSE => "buildEmptyResponse",
                        self::EMIT_HTML_RESPONSE => "buildHtmlResponse",
                        self::EMIT_JSON_RESPONSE => "buildJsonResponse",
                        default => "buildEmptyResponse"
                    };

                    $response = call_user_func("{$class}::{$method}");
                    (new SapiEmitter)->emit($response);
                }
            }

            if (!isset($response)) {
                $resolved->args->exception = $e;
                self::invokeRoute($resolved->route);
            }
        }
    }
}