<?php

class Dispatcher extends RouteCollector
{
    public const
        NOT_FOUND = 0,
        FOUND = 1,
        METHOD_NOT_ALLOWED = 2,
        UNAUTHORIZED = 3,
        NO_CONTENT = 4,
        FORBIDDEN = 5;

    private $routeCollector;

    // TODO: Make the Dispatcher instantiate a new RouteCollector instead of parsing it as a parameter.
    public function __construct(RouteCollector $routeCollector)
    {
        $this->routeCollector = $routeCollector;
    }

    /**
     * Performs routing using the given request.
     * @param  Request $request A request that which will be dispatched through routes to handlers.
     * @return intger Response type, indicating the immediate result of routing the given request.
     */
    public function dispatch(Request $request)
    {
        $routeFound = false;
        $isPreflight = false;
        $allowedMethods = [];

        if ($request->getMethod() == "OPTIONS")
            $isPreflight = true;

        foreach ($this->routeCollector->routes as $method => $route)
        {
            foreach ($route as $path => $callback)
            {
                // Converts urls like '/guilds/{guild_id}/incidents/{incident_id}' to a regular expression.
                $pattern = "@^" . preg_replace('/\\\{[a-zA-Z0-9\_\\\}]+/', '([a-zA-Z0-9\-\_]+)', preg_quote($path)) . "$@D";

                // Check if the requested path matches the expression.
                if (preg_match($pattern, $request->getPath(), $matches))
                {
                    $routeFound = true;

                    if ($isPreflight)
                    {
                        array_push($allowedMethods, $method);
                    }
                    else if ($request->getMethod() === $method)
                    {
                        // Removes the first match as it is just the requested path.
                        array_shift($matches);

                        $matches['request'] = $request;

                        // Calls the callback with its parameters as the matches.
                        $result = call_user_func_array($callback, $matches);

                        return $result;
                    }
                }
            }
        }

        // Check whether the request was a preflight.
        if ($isPreflight)
        {
            header('Access-Control-Allow-Methods: ' . implode(', ', $allowedMethods));
            die();
        }

        // Check whether a route was found, knowing that the requested method wasn't found.
        if ($routeFound)
            return self::METHOD_NOT_ALLOWED;

        // No route found for the requested url.
        return self::NOT_FOUND;
    }
}
