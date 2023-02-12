<?php

namespace Tnapf\Router;

use Closure;
use HttpSoft\Emitter\SapiEmitter;
use HttpSoft\Message\Response;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\UploadedFile;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tnapf\Router\Exceptions\HttpNotFound;
use stdClass;
use Throwable;
use Tnapf\Router\Enums\Methods;
use Tnapf\Router\Exceptions\HttpInternalServerError;
use Tnapf\Router\Routing\Controller;
use Tnapf\Router\Routing\Route;

final class Router {
    /**
     * @var Route[]
     */
    private static array $routes = [];

    /**
     * @var array
     */
    private static array $mount;

    /**
     * @var Route[][]
     */
    private static array $catchers = [
        HttpNotFound::class => [],
        HttpInternalServerError::class => []
    ];

    /**
     * @param string $uri
     * @param class-string<Controller> $controller
     * @return Route
     */
    public static function get(string $uri, string|Closure $controller): Route
    {
        $route = new Route($uri, $controller, Methods::GET);
        
        self::addRoute($route);
        
        return $route;
    }

    /**
     * @param string $uri
     * @param class-string<Controller> $controller
     * @return Route
     */
    public static function post(string $uri, string|Closure $controller): Route
    {
        $route = new Route($uri, $controller, Methods::POST);
        
        self::addRoute($route);

        return $route;
    }

    /**
     * @param string $uri
     * @param class-string<Controller> $controller
     * @return Route
     */
    public static function put(string $uri, string|Closure $controller): Route
    {
        $route = new Route($uri, $controller, Methods::PUT);
        
        self::addRoute($route);

        return $route;
    }

    /**
     * @param string $uri
     * @param class-string<Controller> $controller
     * @return Route
     */
    public static function delete(string $uri, string|Closure $controller): Route
    {
        $route = new Route($uri, $controller, Methods::DELETE);
        
        self::addRoute($route);

        return $route;
    }

    /**
     * @param string $uri
     * @param class-string<Controller> $controller
     * @return Route
     */
    public static function options(string $uri, string|Closure $controller): Route
    {
        $route = new Route($uri, $controller, Methods::OPTIONS);
        
        self::addRoute($route);

        return $route;
    }

    /**
     * @param string $uri
     * @param class-string<Controller> $controller
     * @return Route
     */
    public static function head(string $uri, string|Closure $controller): Route
    {
        $route = new Route($uri, $controller, Methods::HEAD);
        
        self::addRoute($route);

        return $route;
    }

    /**
     * @param string $uri
     * @param class-string<Controller> $controller
     * @return Route
     */
    public static function all(string $uri, string|Closure $controller): Route
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
            foreach (self::$mount["beforeMiddleware"] as $before) {
                $route->before($before);
            }
            
            foreach (self::$mount["afterMiddleware"] as $after) {
                $route->before($after);
            }
        }

        self::$routes[$route->uri] = &$route;
    }

    public static function mount(string $baseUri, Closure $mounting, array $beforeMiddleware = [], array $afterMiddleware = []): void
    {
        self::$mount = compact("baseUri", "beforeMiddleware", "afterMiddleware");

        call_user_func($mounting);

        self::$mount = [];
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

                if (isset($matches[$index + 1]) && isset($matches[$index + 1][0]) && is_array($matches[$index + 1][0])) {
                    if ($matches[$index + 1][0][1] > -1) {
                        $args->$name = trim(substr($match[0][0], 0, $matches[$index + 1][0][1] - $match[0][1]), '/');
                    }
                }

                $args->$name = isset($match[0][0]) && $match[0][1] != -1 ? trim($match[0][0], '/') : null;
            }

            $matchedRoute->route = $route;
            $matchedRoute->args = $args;
        }

        return $matchedRoute ?? null;
    }

    /**
     * @param class-string<Throwable> $exceptionToCatch
     * @param string|Closure $controller
     */
    public static function catch(string $toCatch, string|Closure $controller, ?string $uri = "/(.*)"): Route
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
                
                                if (!is_callable($controller)) {
                                    $response = $controller::handle(...$params);
                                } else {
                                    $response = $controller(...$params);
                                }
                
                                return $response;
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

                if (!is_callable($controller)) {
                    $response = $controller::handle(...$params);
                } else {
                    $response = $controller(...$params);
                }

                return $response;
            };
        }

        return $nexts[0](new Response);
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

        foreach ($_FILES as $key => $file) {
            $_FILES[$key] = new UploadedFile($file["tmp_name"], $file["size"], $file["error"], $file["type"]);
        }

        $request = new ServerRequest($_SERVER, $_FILES, $_COOKIE, $_GET, $_POST, $_SERVER["REQUEST_METHOD"], $_SERVER["REQUEST_URI"], getallheaders(), "php://input");

        try {
            if ($resolved === null) {
                throw new HttpNotFound($request);
            }

            $response = self::invokeRoute($resolved->route, $resolved->args, $request);
        } catch (Throwable $e) {
            if (in_array($e::class, array_keys(self::$catchers))) {
                $resolved = self::resolveRoute(self::$catchers[$e::class]);
            } else {
                $resolved = self::resolveRoute(self::$catchers[HttpInternalServerError::class]);
            }

            if ($resolved === null) {
                throw $e;
            }

            $resolved->args->exception = $e;

            $response = self::invokeRoute($resolved->route, $resolved->args, $request);
        }
        
        $emitter = new SapiEmitter();
        $emitter->emit($response);
    }
}