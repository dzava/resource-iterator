<?php

namespace Dzava\ResourceIterator;

use GuzzleHttp\Client;
use Illuminate\Support\Str;

abstract class ResourceIterator
{
    /** @var int $maxNumberOfRequests */
    protected $maxNumberOfRequests = 0;
    /** @var int $requestCount */
    protected $requestCount = 0;
    /** @var \GuzzleHttp\Psr7\Response $lastResponse */
    protected $lastResponse;
    /** @var string $url */
    protected $url;
    /** @var Client $client */
    protected $client;

    public $config = [
        'page' => 'page',
        'data' => 'data',
    ];

    public function __construct($url)
    {
        $this->url = $url;
    }

    /**
     * @return \Generator
     */
    public function items()
    {
        $url = $this->url;
        do {
            foreach ($this->get($url) as $result) {
                yield $result;
            }

            if ($this->maxNumberOfRequests > 0 && $this->requestCount >= $this->maxNumberOfRequests) {
                break;
            }
        } while (($url = $this->nextPageUrl()));
    }

    /**
     * An array of all the results
     *
     * @return array
     */
    public function toArray()
    {
        return iterator_to_array($this->items());
    }

    /**
     * The maximum number of requests that may be executed before stopping
     *
     * @param int $max
     * @return $this
     */
    public function maxRequests($max)
    {
        $this->maxNumberOfRequests = $max;

        return $this;
    }

    /**
     * Set the Guzzle Client used to perform the requests
     *
     * @param Client $client
     * @return $this
     */
    public function withClient($client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @param array $config
     * @return \Dzava\ResourceIterator\ResourceIterator
     */
    public function withConfig($config)
    {
        $this->config = array_merge($this->config, $config);

        return $this;
    }

    /**
     * The value of the 'page' param used to fetch the next page
     *
     * @return bool|string
     */
    protected function nextPage()
    {
        throw new \RuntimeException(static::class . " should override 'nextPage' or 'nextPageUrl'");
    }

    /**
     * The url for the next page or false when there are no more pages
     *
     * @return bool|string
     */
    protected function nextPageUrl()
    {
        $nextPage = $this->nextPage();

        if ($nextPage === false) {
            return false;
        }

        return $this->setQueryParam($this->url, $this->config['page'], $nextPage);
    }

    /**
     * Execute the request
     *
     * @param string $url
     * @return mixed
     */
    protected function get($url)
    {
        $this->lastResponse = $this->getClient()->get($url);
        $this->requestCount++;

        return $this->parseResponse();
    }

    /**
     * Get the http client used to perform the requests
     *
     * @return \GuzzleHttp\Client
     */
    protected function getClient()
    {
        return $this->client ?? ($this->client = new Client());
    }

    /**
     * Retrieve the results from the response
     *
     * @return array
     */
    protected function parseResponse()
    {
        return $this->decodedResponse($this->config['data'], []);
    }

    /**
     * @param string|null $path
     * @param mixed|null $default
     * @return mixed
     */
    protected function decodedResponse($path = null, $default = null)
    {
        return object_get(json_decode($this->lastResponse->getBody()), $path, $default);
    }

    /**
     * @param string $url
     * @param string $param
     * @param string $value
     * @return string
     */
    protected function setQueryParam($url, $param, $value)
    {
        // Replace the param with the new value if it exists
        if ($this->getQueryParam($url, $param) !== null) {
            return preg_replace("/([?&])$param=[^&]+/", "\$1$param=$value", $url);
        }

        // Append the value when there are other query params
        if (Str::contains($url, '?')) {
            return "$url&$param=$value";
        }

        // No other params are present in the url
        return "$url?$param=$value";
    }

    /**
     * @param string $url
     * @param string $name
     * @return string|null
     */
    protected function getQueryParam($url, $name)
    {
        preg_match("/[?&]$name=([^&]+)/", $url, $matches);

        return $matches[1] ?? null;
    }
}
