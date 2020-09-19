<?php

namespace Dzava\ResourceIterator\Tests\Integration;

use Dzava\ResourceIterator\PagedResourceIterator;
use Dzava\ResourceIterator\ResourceIterator;
use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase
{
    /** @var ResourceIterator $resource */
    protected $resource;

    private array $firstPageResults = ['George', 'Janet', 'Emma', 'Eve', 'Charles', 'Tracey'];
    private array $secondPageResults = ['Michael', 'Lindsay', 'Tobias', 'Byron', 'George', 'Rachel'];
    private array $allResults;

    protected function setUp()
    {
        parent::setUp();

        $this->resource = $this->getPagedResourceIterator('https://reqres.in/api/users');

        $this->allResults = [...$this->firstPageResults, ...$this->secondPageResults];
    }

    /** @test */
    public function can_fetch_all_pages()
    {
        $items = $this->resource->toArray();

        $this->assertEquals($this->allResults, $items);
    }

    /** @test */
    public function can_limit_the_max_number_of_requests()
    {
        $this->resource->maxRequests(1);

        $this->assertEquals($this->firstPageResults, $this->resource->toArray());
    }

    /** @test */
    public function can_start_at_a_page_that_is_not_the_first_one()
    {
        $this->resource = $this->getPagedResourceIterator('https://reqres.in/api/users?page=2');

        $this->assertEquals($this->secondPageResults, $this->resource->toArray());
    }

    /** @test */
    public function does_not_fail_if_the_response_is_empty()
    {
        $this->resource = $this->getPagedResourceIterator('https://reqres.in/api/users?page=100');

        $this->assertEmpty($this->resource->toArray());
    }

    protected function getPagedResourceIterator($url)
    {
        return new class($url) extends PagedResourceIterator {
            public function toArray()
            {
                return array_map(function ($item) {
                    return $item->first_name;
                }, parent::toArray());
            }
        };
    }

}
