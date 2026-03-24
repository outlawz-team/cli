<?php

namespace App\Providers;

use App\Http\Controllers\Api\SeedController;
use Illuminate\Support\ServiceProvider;

class ApiServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes()
    {
        $this->registerApiRoute('GET', 'seeds', [SeedController::class, 'index']);
        $this->registerApiRoute('GET', 'seeds/(?P<id>[\d]+)', [SeedController::class, 'show']);
    }

    protected function registerApiRoute(string $method, string $route, array $callback)
    {
        register_rest_route('radicle/v1', $route, [
            'methods' => $method,
            'callback' => function ($request) use ($callback) {
                $controller = app($callback[0]);
                $method = $callback[1];

                // Extract route parameters and merge with request data
                $params = array_merge(
                    $request->get_url_params(),
                    $request->get_query_params(),
                    $request->get_json_params() ?: [],
                    $request->get_body_params()
                );

                // Call controller method with parameters
                return $controller->$method($request, ...$this->extractMethodParams($callback, $params));
            },
            'permission_callback' => '__return_true',
        ]);
    }

    protected function extractMethodParams(array $callback, array $params): array
    {
        $reflection = new \ReflectionMethod($callback[0], $callback[1]);
        $methodParams = [];

        foreach ($reflection->getParameters() as $param) {
            $name = $param->getName();

            // Skip WP_REST_Request parameter
            if ($name === 'request') {
                continue;
            }

            if (isset($params[$name])) {
                $methodParams[] = $params[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $methodParams[] = $param->getDefaultValue();
            } else {
                $methodParams[] = null;
            }
        }

        return $methodParams;
    }
}
