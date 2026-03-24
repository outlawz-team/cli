# REST API development

Radicle provides a clean, organized way to build REST API endpoints using controllers and the WordPress REST API.

## Directory structure

```
app/Http/
└── Controllers/
    └── Api/
        ├── SeedController.php
        └── PostController.php
```

## Creating API controllers

API controllers follow Laravel conventions and use your Eloquent models:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Models\Seed;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class SeedController
{
    public function index(WP_REST_Request $request): WP_REST_Response
    {
        $seeds = Seed::published()->get();

        $data = $seeds->map(function ($seed) {
            return [
                'id' => $seed->ID,
                'title' => $seed->title(),
                'excerpt' => $seed->excerpt(30),
                'permalink' => $seed->permalink(),
            ];
        });

        return new WP_REST_Response($data);
    }

    public function show(WP_REST_Request $request, ?int $id = null): WP_REST_Response|WP_Error
    {
        $seed = Seed::find($id);

        if (!$seed) {
            return new WP_Error('not_found', 'Seed not found', ['status' => 404]);
        }

        return new WP_REST_Response([
            'id' => $seed->ID,
            'title' => $seed->title(),
            'content' => $seed->content(),
        ]);
    }
}
```

## Registering routes

Routes are registered in the `ApiServiceProvider`:

```php
<?php

namespace App\Providers;

use App\Http\Controllers\Api\SeedController;
use Illuminate\Support\ServiceProvider;

class ApiServiceProvider extends ServiceProvider
{
    public function boot()
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes()
    {
        $this->registerApiRoute('GET', 'seeds', [SeedController::class, 'index']);
        $this->registerApiRoute('GET', 'seeds/(?P<id>[\d]+)', [SeedController::class, 'show']);
    }
}
```

## Available endpoints

The example implementation provides:

- `GET /wp-json/radicle/v1/seeds` - List seeds with pagination and search
- `GET /wp-json/radicle/v1/seeds/{id}` - Get single seed

## Request parameters

Controllers automatically receive URL parameters, query strings, and JSON/body data:

```php
public function index(WP_REST_Request $request): WP_REST_Response
{
    $page = $request->get_param('page') ?? 1;
    $search = $request->get_param('search');

    // Use parameters in your logic
}
```

## Response format

Return structured JSON responses:

```php
return new WP_REST_Response([
    'id' => $seed->ID,
    'title' => $seed->title(),
    'meta' => [
        'featured' => $seed->getMeta('featured', false),
    ],
]);
```

## Error handling

Return `WP_Error` for error responses:

```php
if (!$seed) {
    return new WP_Error(
        'seed_not_found',
        'Seed not found',
        ['status' => 404]
    );
}
```

## Testing API endpoints

Create feature tests for your API endpoints:

```php
<?php

it('can retrieve seeds via API', function () {
    $request = new WP_REST_Request('GET', '/radicle/v1/seeds');
    $response = $this->server->dispatch($request);

    expect($response->get_status())->toBe(200)
        ->and($response->get_data())->toBeArray();
});
```

## Best practices

- Use Eloquent models for data access
- Return consistent JSON structure
- Handle errors with proper HTTP status codes
- Add pagination for list endpoints
- Validate input parameters
- Test all endpoints with Pest