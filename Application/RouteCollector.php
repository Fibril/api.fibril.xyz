<?php

class RouteCollector
{
    protected $routes = [
        'GET' => [],
        'POST' => []
    ];
    
    public function get($route, $callback)
    {
        $this->addRoute('GET', $route, $callback);
    }

    public function post($route, $callback)
    {
        $this->addRoute('POST', $route, $callback);
    }

    public function put($route, $callback)
    {
        $this->addRoute('PUT', $route, $callback);
    }

    public function patch($route, $callback)
    {
        $this->addRoute('PATCH', $route, $callback);
    }

    public function delete($route, $callback)
    {
        $this->addRoute('DELETE', $route, $callback);
    }

    public function head($route, $callback)
    {
        $this->addRoute('HEAD', $route, $callback);
    }

    private function addRoute($method, $url, $callback)
    {
        // If the callback isn't a method, try and set it as an object.
        if (!is_callable($callback))
        {
            $callback = new $callback();

            // Check again whether the callback is callable. If not, trigger an error.
            if (!is_callable($callback))
                trigger_error("Callback isn't callable. Make sure that the callback is a valid class name or a callable method.", E_USER_ERROR);
        }

        // The callback is a callable method/object.

        $this->routes[$method][$url] = $callback;
    }
}
