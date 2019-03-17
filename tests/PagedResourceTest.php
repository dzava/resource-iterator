<?php

namespace Tests\Feature;

use Dzava\ResourceIterator\PagedResourceIterator;
use Dzava\ResourceIterator\ResourceIterator;
use Illuminate\Support\Arr;
use PHPUnit\Framework\TestCase;
use stdClass;

class PagedResourceTest extends TestCase
{
    /** @var ResourceIterator $resource */
    protected $resource;

    protected function setUp()
    {
        parent::setUp();

        $this->resource = new PagedResourceIterator('users?page=1');
    }

    /** @test */
    public function can_fetch_all_pages()
    {
        $client = $this->createClient([
            'users?page=1' => $this->createPaginatedResponseData(1, 3),
            'users?page=2' => $this->createPaginatedResponseData(2, 3),
            'users?page=3' => $this->createPaginatedResponseData(3, 3),
        ]);
        $this->resource->withClient($client);

        $this->assertEquals(['user 1', 'user 2', 'user 3', 'user 4', 'user 5', 'user 6'], $this->resource->toArray());
    }

    /** @test */
    public function can_limit_the_max_number_of_requests()
    {
        $client = $this->createClient([
            'users?page=1' => $this->createPaginatedResponseData(1, 3),
            'users?page=2' => $this->createPaginatedResponseData(2, 3),
        ]);

        $this->resource->withClient($client)->maxRequests(2);

        $this->assertEquals(['user 1', 'user 2', 'user 3', 'user 4'], $this->resource->toArray());
    }

    /** @test */
    public function can_start_at_a_page_that_is_not_the_first_one()
    {
        $client = $this->createClient([
            'users?current_page=2' => $this->createPaginatedResponseData(2, 3),
            'users?current_page=3' => $this->createPaginatedResponseData(3, 3),
        ]);
        $this->resource = (new PagedResourceIterator('users?current_page=2'))
            ->withConfig(['page' => 'current_page'])
            ->withClient($client);

        $this->assertEquals(['user 3', 'user 4', 'user 5', 'user 6'], $this->resource->toArray());
    }

    /** @test */
    public function keeps_other_query_parameters()
    {
        $client = $this->createClient([
            'users?order=name&page=1&per_page=2' => $this->createPaginatedResponseData(1, 2),
            'users?order=name&page=2&per_page=2' => $this->createPaginatedResponseData(2, 2),
        ]);
        $this->resource = new PagedResourceIterator('users?order=name&page=1&per_page=2');

        $this->resource->withClient($client);

        $this->assertEquals(['user 1', 'user 2', 'user 3', 'user 4'], $this->resource->toArray());
    }

    /** @test */
    public function adds_the_query_parameter_if_it_is_missing()
    {
        $client = $this->createClient([
            'users' => $this->createPaginatedResponseData(1, 2),
            'users?page=2' => $this->createPaginatedResponseData(2, 2),
        ]);
        $this->resource = (new PagedResourceIterator('users'))
            ->withClient($client);

        $this->assertEquals(['user 1', 'user 2', 'user 3', 'user 4'], $this->resource->toArray());
    }

    /** @test */
    public function keeps_other_query_parameters_when_the_page_param_is_missing()
    {
        $client = $this->createClient([
            'users?order=name&per_page=2' => $this->createPaginatedResponseData(1, 2),
            'users?order=name&per_page=2&page=2' => $this->createPaginatedResponseData(2, 2),
        ]);
        $this->resource = (new PagedResourceIterator('users?order=name&per_page=2'))
            ->withClient($client);

        $this->assertEquals(['user 1', 'user 2', 'user 3', 'user 4'], $this->resource->toArray());
    }

    /** @test */
    public function retrieving_the_full_response()
    {
        $client = $this->createClient(['users?page=1' => ['foo', 'bar']]);
        $this->resource
            ->withClient($client)
            ->withConfig(['data' => '']);

        $this->assertEquals([['foo', 'bar']], $this->resource->toArray());
    }

    /** @test */
    public function does_not_fail_if_the_response_is_empty()
    {
        $client = $this->createClient(['users?page=1' => []]);
        $this->resource->withClient($client);

        $this->assertEmpty($this->resource->toArray());
    }

    protected function createClient($map)
    {
        $clientMock = $this->getMockBuilder(stdClass::class)->setMethods(['get'])->getMock();
        $map = collect($map);

        $responses = $map->values()->map(\Closure::fromCallable([$this, 'createResponse']));
        $urls = $map->keys()->map(function ($url) {
            return [$this->equalTo($url)];
        });

        $clientMock
            ->expects(self::exactly($map->count()))
            ->method('get')
            ->withConsecutive(...$urls)
            ->willReturnOnConsecutiveCalls(...$responses);

        return $clientMock;
    }

    protected function createPaginatedResponseData($currentPage, $totalPages, $perPage = 2)
    {
        $data = collect(range(1, $perPage))->map(function ($index) use ($currentPage, $perPage) {
            $index -= 2;

            return 'user ' . ($currentPage * $perPage + $index);
        })->toArray();

        return [
            'total_pages' => $totalPages,
            'data' => $data,
        ];
    }

    protected function createResponse($data)
    {
        $data = Arr::wrap($data);

        if (!array_key_exists('data', $data)) {
            $data = ['data' => $data];
        }

        $mockObject = $this->getMockBuilder(stdClass::class)
            ->setMethods(['getBody'])
            ->getMock();

        $mockObject
            ->expects(self::atLeast(1))
            ->method('getBody')
            ->willReturn(json_encode($data));

        return $mockObject;
    }
}
