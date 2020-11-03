<?php

use Services\Auth\JwtGuard;

class Dispatcher extends RouteCollector
{
    public const NOT_FOUND = 0, FOUND = 1, METHOD_NOT_ALLOWED = 2, UNAUTHORIZED = 3;

    private $routeCollector;

    // TODO: Make the Dispatcher instantiate a new RouteCollector instead of parsing it as a parameter.
    public function __construct(RouteCollector $routeCollector)
    {
        $this->routeCollector = $routeCollector;
    }

    /**
     * Performs routing using the given request.
     * @param  Request $request A request that which will be dispatched through routes to handlers.
     * @return Dispatcher::NOT_FOUND|Dispatcher::FOUND|Dispatcher::METHOD_NOT_ALLOWED|Dispatcher::UNAUTHORIZED 
     *         Status code indicating the immediate result of routing the given request.
     */
    public function dispatch(Request $request)
    {
        $routeFound = false;
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

                    if ($request->getMethod() === $method)
                    {
                        // Removes the first match as it is just the requested path.
                        array_shift($matches);

                        $matches['request'] = $request;

                        // Calls the callback with its parameters as the matches.
                        $result = call_user_func_array($callback, $matches);

                        if (in_array($result, [self::NOT_FOUND, self::FOUND, self::METHOD_NOT_ALLOWED, self::UNAUTHORIZED]))
                            return $result;

                        return self::FOUND; // TODO: Revise again, as when do we ever actually return self::FOUND?
                    }
                }
            }
        }

        // Check whether a route was found, knowing that the requested method wasn't found.
        if ($routeFound)
            return self::METHOD_NOT_ALLOWED;

        // No route found for the requested url.
        return self::NOT_FOUND;
    }
}
