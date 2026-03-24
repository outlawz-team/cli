<?php

use App\Models\Seed;

beforeEach(function () {
    // Set up REST server for API tests
    global $wp_rest_server;
    $this->server = $wp_rest_server = new WP_REST_Server();

    // Manually register our API routes for testing
    $apiProvider = new \App\Providers\ApiServiceProvider(app());
    $apiProvider->boot();

    do_action('rest_api_init', $this->server);
});

afterEach(function () {
    global $wp_rest_server;
    $this->server = $wp_rest_server = null;
});

it('can retrieve seeds via API index endpoint', function () {
    $request = new WP_REST_Request('GET', '/radicle/v1/seeds');
    $response = $this->server->dispatch($request);

    expect($response->get_status())->toBe(200)
        ->and($response->get_data())->toBeArray()
        ->and($response->get_headers())->toHaveKey('X-WP-Total');
});

it('can retrieve seed via API show endpoint', function () {
    $seeds = Seed::published()->limit(1)->get();

    if ($seeds->isEmpty()) {
        $this->markTestSkipped('No published seeds available for testing');
    }

    $seed = $seeds->first();
    $request = new WP_REST_Request('GET', "/radicle/v1/seeds/{$seed->ID}");
    $response = $this->server->dispatch($request);

    expect($response->get_status())->toBe(200);

    $data = $response->get_data();
    expect($data)->toHaveKey('id')
        ->and($data)->toHaveKey('title')
        ->and($data)->toHaveKey('content')
        ->and($data['id'])->toBe($seed->ID);
});

it('returns 404 for non-existent seed', function () {
    $request = new WP_REST_Request('GET', '/radicle/v1/seeds/99999');
    $response = $this->server->dispatch($request);

    expect($response->get_status())->toBe(404);

    $data = $response->get_data();
    expect($data)->toHaveKey('code')
        ->and($data['code'])->toBe('seed_not_found');
});

it('supports pagination parameters', function () {
    $request = new WP_REST_Request('GET', '/radicle/v1/seeds');
    $request->set_query_params([
        'page' => 1,
        'per_page' => 2,
    ]);

    $response = $this->server->dispatch($request);

    expect($response->get_status())->toBe(200)
        ->and($response->get_data())->toBeArray()
        ->and(count($response->get_data()))->toBeLessThanOrEqual(2);
});

it('supports search parameter', function () {
    $request = new WP_REST_Request('GET', '/radicle/v1/seeds');
    $request->set_query_params([
        'search' => 'sample',
    ]);

    $response = $this->server->dispatch($request);

    expect($response->get_status())->toBe(200)
        ->and($response->get_data())->toBeArray();
});
