<?php

use Roots\Acorn\Application;
use Roots\Acorn\Configuration\Exceptions;
use Roots\Acorn\Configuration\Middleware;

// Bootstrap WordPress environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Set testing environment before any WordPress constants are defined
$_ENV['WP_ENV'] = 'testing';

// Bootstrap WordPress
require_once __DIR__ . '/../public/wp-config.php';

// Ensure WordPress is fully loaded
if (!function_exists('wp_loaded')) {
    require_once ABSPATH . 'wp-settings.php';
}

// Bootstrap Acorn the same way as the mu-plugin
$app = Application::configure()
    ->withProviders()
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->boot();

// Manually set up database connection since Acorn's provider registration is incomplete
$capsule = new \Illuminate\Database\Capsule\Manager();
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => DB_HOST,
    'database' => DB_NAME,
    'username' => DB_USER,
    'password' => DB_PASSWORD,
    'charset' => DB_CHARSET,
    'collation' => $GLOBALS['wpdb']->collate ?? 'utf8mb4_unicode_ci',
    'prefix' => $GLOBALS['wpdb']->prefix,
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();
