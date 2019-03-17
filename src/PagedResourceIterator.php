<?php

namespace Dzava\ResourceIterator;

class PagedResourceIterator extends ResourceIterator
{

    protected $startPage;

    public function __construct($url)
    {
        parent::__construct($url);

        $this->withConfig(['totalPages' => 'total_pages']);
    }

    public function nextPage()
    {
        if (($this->decodedResponse($this->config['totalPages'], $this->startPage) - $this->startPage) < $this->requestCount) {
            return false;
        }

        return $this->startPage + $this->requestCount;
    }

    public function items()
    {
        $this->startPage = $this->getQueryParam($this->url, $this->config['page']) ?? 1;

        return parent::items();
    }
}
