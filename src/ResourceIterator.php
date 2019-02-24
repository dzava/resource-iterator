<?php

namespace Dzava\ResourceIterator;

use GuzzleHttp\Client;
use Illuminate\Support\Str;

abstract class ResourceIterator
{
    protected $maxNumberOfRequests = 0;
    protected $requestCount = 0;
    protected $lastResponse;
    protected $url;
    protected $client;

    public $config = [
        'page' => 'page',
        'data' => 'data',
    ];

    public function __construct($url, $config = [])
    {
        $this->client = new Client();
        $this->url = $url;

        $this->withConfig($config);
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
     * The value of the 'page' param used to fetch the next page
     *
     * @return bool|string
     */
    abstract protected function nextPage();

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
     * Execute the request
     *
     * @param string $url
     * @return mixed
     */
    protected function get($url)
    {
        $response = json_decode($this->client->get($url)->getBody());
        $this->lastResponse = $response;
        $this->requestCount++;

        return $this->parseResponse($response);
    }

    /**
     * @param mixed $response
     * @return array
     */
    protected function parseResponse($response)
    {
        return object_get($response, $this->config['data'], []);
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
        preg_match("/[?&]$name=(\d+)+/", $url, $matches);

        return $matches[1] ?? null;
    }
}
