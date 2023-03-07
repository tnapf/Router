<?php

namespace Tnapf\Router;

use Closure;
use HttpSoft\Emitter\SapiEmitter;
use HttpSoft\Message\Response;
use HttpSoft\ServerRequest\ServerRequestCreator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use Throwable;
use Tnapf\Router\Enums\Methods;
use Tnapf\Router\Exceptions\HttpException;
use Tnapf\Router\Exceptions\HttpInternalServerError;
use Tnapf\Router\Routing\Route;

final class Router {
    public const EMIT_EMPTY_RESPONSE = 1;
    public const EMIT_HTML_RESPONSE = 2;
    public const EMIT_JSON_RESPONSE = 3;

    /**
     * @var Route[]
     */
    private static array $routes = [];

    /**
     * @var array
     */
    private static array $mount = [];

    /**
     * @var int The type of emit
     */
    private static int $emitHttpExceptions = 0;

    /**
     * @var Route[][]
     */
    private static array $catchers = [
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
     * @param array|Closure $controller
     * @return Route
     */
    public static function get(string $uri, array|Closure $controller): Route
    {
        $route = new Route($uri, $controller, Methods::GET);
        
        self::addRoute($route);
        
        return $route;
    }

    /**
     * @param string $uri
     * @param array|Closure $controller
     * @return Route
     */
    public static function post(string $uri, array|Closure $controller): Route
    {
        $route = new Route($uri, $controller, Methods::POST);
        
        self::addRoute($route);

        return $route;
    }

    /**
     * @param string $uri
     * @param array|Closure $controller
     * @return Route
     */
    public static function put(string $uri, array|Closure $controller): Route
    {
        $route = new Route($uri, $controller, Methods::PUT);
        
        self::addRoute($route);

        return $route;
    }

    /**
     * @param string $uri
     * @param array|Closure $controller
     * @return Route
     */
    public static function delete(string $uri, array|Closure $controller): Route
    {
        $route = new Route($uri, $controller, Methods::DELETE);
        
        self::addRoute($route);

        return $route;
    }

    /**
     * @param string $uri
     * @param array|Closure $controller
     * @return Route
     */
    public static function options(string $uri, array|Closure $controller): Route
    {
        $route = new Route($uri, $controller, Methods::OPTIONS);
        
        self::addRoute($route);

        return $route;
    }

    /**
     * @param string $uri
     * @param array|Closure $controller
     * @return Route
     */
    public static function head(string $uri, array|Closure $controller): Route
    {
        $route = new Route($uri, $controller, Methods::HEAD);
        
        self::addRoute($route);

        return $route;
    }

    /**
     * @param string $uri
     * @param array|Closure $controller
     * @return Route
     */
    public static function all(string $uri, array|Closure $controller): Route
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
            foreach (self::$mount["beforeMiddleware"] ?? [] as $before) {
                $route->before($before);
            }
            
            foreach (self::$mount["afterMiddleware"] ?? [] as $after) {
                $route->after($after);
            }
        }

        self::$routes[$route->uri] = &$route;
    }

    public static function group(string $baseUri, Closure $grouping, array $beforeMiddleware = [], array $afterMiddleware = []): void
    {
        $oldMount = self::$mount;

        if (empty(self::$mount['baseUri'])) {
            self::$mount = compact("baseUri", "beforeMiddleware", "afterMiddleware");
        } else {
            self::$mount['baseUri'] .= $baseUri;
            self::$mount['beforeMiddleware'] = array_merge(self::$mount['beforeMiddleware'], $beforeMiddleware);
            self::$mount['afterMiddleware'] = array_merge(self::$mount['afterMiddleware'], $afterMiddleware);
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
     * @param array|Closure $controller
     */
    public static function catch(string $toCatch, array|Closure $controller, ?string $uri = "/(.*)"): Route
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
     * @param Route $route
     * @param stdClass|null $args
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    private static function invokeRoute(Route $route, ?stdClass $args = null, ServerRequestInterface $request): ResponseInterface
    {
        if ($args === null) {
            $args = new stdClass;
        }

        $params = [$request, null, $args];
        
        $befores = $route->getBefores();
        
        $basicMiddleware = static function (ServerRequestInterface $request, ResponseInterface $response, stdClass $args, Closure $next) {
            $next($response);
        };

        array_unshift($befores, $basicMiddleware);

        $nexts = [];

        foreach (array_keys($befores) as $key) {
            $nexts[] = static function (ResponseInterface $response, mixed ...$extra) use ($route, $basicMiddleware, $params, $befores, $key, &$nexts): ResponseInterface {
                if ($key === count($befores)-1) {
                    $controller = $route->controller;

                    if (!empty($route->getAfters()) && !isset($afters)) {
                        $nexts = [];
                        $afters = $route->getAfters();
                        array_unshift($afters, $basicMiddleware);

                        foreach (array_keys($afters) as $key) {
                            $nexts[] = static function (ResponseInterface $response, mixed ...$extra) use ($params, $afters, $key, &$nexts): ResponseInterface {
                                $controller = $afters[$key+1] ?? null;
                                $params[] = $nexts[$key+1] ?? null;

                                if ($controller === null) {
                                    return $response;
                                }
                
                                $params[1] = $response;
                
                                $params = array_merge($params, $extra);

                                return !is_callable($controller)
                                    ? call_user_func("{$controller[0]}::{$controller[1]}", ...$params)
                                    : $controller(...$params);
                            };
                        }

                        $params[] = $nexts[0];
                    }
                } else {
                    $controller = $befores[$key+1];
                    $params[] = $nexts[$key+1];
                }

                $params[1] = $response;

                $params = array_merge($params, $extra);


                return !is_callable($controller)
                    ? call_user_func("{$controller[0]}::{$controller[1]}", ...$params)
                    : $controller(...$params);
            };
        }

        return $nexts[0](new Response);
    }

    /**
     * 
     */
    public static function emitHttpExceptions(int $type): void
    {
        self::$emitHttpExceptions = $type;
    }

    public static function run(): void
    {
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

            $response = self::invokeRoute($resolved->route, $resolved->args, $request);
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
                }
            }

            if (!isset($response)) {
                $resolved->args->exception = $e;
                $response = self::invokeRoute($resolved->route, $resolved->args, $request);
            }
        }
        
        (new SapiEmitter())->emit($response);
    }
}