<?php

namespace Tnapf\Router;

use Closure;
use HttpSoft\Emitter\SapiEmitter;
use HttpSoft\Message\Response;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\UploadedFile;
use Tnapf\Router\Exceptions\HttpNotFound;
use stdClass;
use Tnapf\Router\Enums\Methods;
use Tnapf\Router\Routing\Controller;
use Tnapf\Router\Routing\Route;

final class Router {
    public static array $routes = [];

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

    public static function addRoute(Route &$route): void
    {
        self::$routes[] = &$route;
    }

    private static function resolveRoute(?string $uri = null, ?Methods $method = null): ?stdClass
    {
        $routeMatches = static function (Route $route, string $requestUri, array|null &$matches) use (&$argNames): bool
        {
            $argNames = [];

            $routeParts = explode("/", $route->uri);

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

        if ($uri === null) {
            $uri = $_SERVER["REQUEST_URI"];
        }

        if ($method === null) {
            $method = Methods::from($_SERVER["REQUEST_METHOD"]);
        }

        foreach (self::$routes as $route) {
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

                $name = $argNames[$argsIterator++];

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

    public static function run(): void
    {
        $resolved = self::resolveRoute();

        if ($resolved === null) {
            throw new HttpNotFound($_SERVER["REQUEST_URI"]);
        }
        
        /** @var Route $route */
        $route = $resolved->route;

        foreach ($_FILES as $key => $file) {
            $_FILES[$key] = new UploadedFile($file["tmp_name"], $file["size"], $file["error"], $file["type"]);
        }

        $request = new ServerRequest($_SERVER, $_FILES, $_COOKIE, $_GET, $_POST, $_SERVER["REQUEST_METHOD"], $_SERVER["REQUEST_URI"], getallheaders(), "php://input");

        $params = [$request, new Response, $resolved->args, $route, microtime(true)];

        if (!is_callable($route->controller)) {
            $response = $route->controller::handle(...$params);
        } else {
            /** @var Closure $controller */
            $controller = $route->controller;

            $response = $controller(...$params);
        }
        
        $emitter = new SapiEmitter();
        $emitter->emit($response);
    }
}