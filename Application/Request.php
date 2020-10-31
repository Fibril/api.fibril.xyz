<?php

class Request
{
    private $method;
    private $path;
    private $query;
    private $data;
    private $clientIp;
    private $headers;

    /**
     * Represents a request.
     *
     * @param string $method The request method.
     * @param string $url The full url that was requested.
     * @param string $data A string of JSON data that goes along with e.g. a POST request.
     * @param array $headers An associative array of all the HTTP headers.
     */
    public function __construct(string $method = null, string $url = null, string $data = null, array $headers = null)
    {
        $urlParts = explode('?', $url ?? $_SERVER['REQUEST_URI'], 2);

        if (count($urlParts) > 1)
            parse_str($urlParts[1], $query);

        $this->method = $method ?? $_SERVER['REQUEST_METHOD'];
        $this->path = $urlParts[0];
        $this->query = $query ?? array();
        $this->data = $data ?? file_get_contents('php://input');
        $this->clientIp = $_SERVER['REMOTE_ADDR']; // $_SERVER['HTTP_X_FORWARDED_FOR']
        $this->headers = $headers ?? apache_request_headers();
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getPath()
    {
        return $this->path;
    }

    /**
     * Gets query parameters.
     *
     * @param string $parameter
     * @return array If no specific parameter has been specified.
     * @return string If a specific parameter has been specified.
     * @return false If the given parameter wasn't found.
     */
    public function getQuery($parameter = null)
    {
        if (isset($parameter))
        {
            if (!isset($this->query[$parameter]))
                return false;

            return $this->query[$parameter];
        }

        return $this->query;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getData()
    {
        // FIXME: Data MIGHT not be json. It could be an image or some other binary something.
        return json_decode($this->data, true);
    }

    public function getClientIp()
    {
        return $this->clientIp;
    }

    public function __toString()
    {
        // TODO: Do not hardcode values such as the domain name.
        return $this->method . ' https://api.fibril.xyz' . $this->path . ($this->query ? '?' . http_build_query($this->query) : '');
    }
}
