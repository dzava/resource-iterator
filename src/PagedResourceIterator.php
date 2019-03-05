<?php

namespace Dzava\ResourceIterator;

class PagedResourceIterator extends ResourceIterator
{

    protected $startPage;

    public function __construct($url, $config = [])
    {
        parent::__construct($url, array_merge($config, ['totalPages' => 'total_pages']));

        $this->startPage = $this->getQueryParam($this->url, $this->config['page']) ?? 1;
    }

    public function nextPage()
    {
        if (($this->decodedResponse($this->config['totalPages'], $this->startPage) - $this->startPage) < $this->requestCount) {
            return false;
        }

        return $this->startPage + $this->requestCount;
    }
}
