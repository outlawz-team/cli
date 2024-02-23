<?php

namespace App\Providers;

use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\DatabaseServiceProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->alias('db', ConnectionResolverInterface::class);
        $this->app->register(DatabaseServiceProvider::class);
    }
}
