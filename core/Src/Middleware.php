<?php
namespace Src;

use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use FastRoute\DataGenerator\MarkBased;
use FastRoute\Dispatcher\MarkBased as Dispatcher;
use Src\Traits\SingletonTrait;

class Middleware
{
    use SingletonTrait;

    private RouteCollector $middlewareCollector;

    private function __construct()
    {
        $this->middlewareCollector = new RouteCollector(new Std(), new MarkBased());
    }

    public function add($httpMethod, string $route, array $action): void
    {
        $this->middlewareCollector->addRoute($httpMethod, $route, $action);
    }

    public function group(string $prefix, callable $callback): void
    {
        $this->middlewareCollector->addGroup($prefix, $callback);
    }

    public function runMiddlewares(string $httpMethod, string $uri): Request
    {
        $request = new Request();

        $routeMiddleware = app()->settings->app['routeMiddleware'] ?? [];

        $dispatcherMiddleware = new Dispatcher($this->middlewareCollector->getData());
        $routeInfo = $dispatcherMiddleware->dispatch($httpMethod, $uri);

        $middlewares = $routeInfo[1] ?? [];

        foreach ($middlewares as $middleware) {
            $args = explode(':', $middleware);
            if (isset($routeMiddleware[$args[0]])) {
                $obj = new $routeMiddleware[$args[0]];
                $obj->handle($request, $args[1] ?? null);
            }
        }

        return $request;
    }
}