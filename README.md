This package provides the foundation for iterating over paginated json apis.

## Installation

You can install the package via composer:

```bash
composer require dzava/resource-iterator
```

## Usage

```php
use Dzava\ResourceIterator\PagedResourceIterator;

// Given a response with the following structure
// {
//     "total_pages": 4,
//     "data": []
// }
$users = (new PagedResourceIterator('https://example.com/api/users/'))->toArray();


// Given a response with the following structure
// {
//     "pagination: {
//         "total_pages": 4,
//     }
//     "data": []
// }
$users = (new PagedResourceIterator('https://example.com/api/users/'))
    ->withConfig(['totalPages' => 'pagination.total_pages'])
    ->toArray();
```

Use the `withConfig` method to override the default configuration.

```php
[
    'page' => 'page', // name of the query param that indicates the current page
    'data' => 'data', // the response field that contains the data
    'totalPages' => 'total_pages' // the response field that contains the total number of pages
]
```


## Custom iterators
To create a custom iterator simply extend the `ResourceIterator` class and implement the `nextPage` method.
The method should return the value of the `page` query parameter that points to the next page or `false` when there are no more pages.
If the url for the next page is more complex you can override the `nextPageUrl` method and return the complete url for the next page or `false`.

```php
use Dzava\ResourceIterator\ResourceIterator;

class GithubResourceIterator extends ResourceIterator
{
    public function __construct($url)
    {
        parent::__construct($url);

        $this->withConfig(['data' => null]);
    }

    protected function nextPageUrl()
    {
        preg_match('/<(.*?)>.*?rel="next"/', $this->lastResponse->getHeader('Link')[0], $matches);

        return $matches[1] ?? false;
    }
}


$iterator = new GithubResourceIterator('https://api.github.com/orgs/laravel/repos');

foreach ($iterator->items() as $repo) {
    echo "{$repo->full_name}\n";
}
```

## License

The [MIT License (MIT)](LICENSE.md).
