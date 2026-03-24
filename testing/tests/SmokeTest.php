<?php

// phpcs:disable

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

it('should return a 200 status code', function () {
    $client = new GuzzleHttp\Client([
        // Disable SSL verification on local/development environments
        'verify' => env('WP_ENV') === 'production' || env('WP_ENV') === 'staging',
    ]);
    $response = $client->request('GET', env('WP_HOME'));

    $this->assertEquals(200, $response->getStatusCode());
});
