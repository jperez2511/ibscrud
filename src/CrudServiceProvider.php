<?php

namespace Icebearsoft\Kitukizuri;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Routing\Router;

class CrudServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(Router $router)
    {        
        $this->loadViewsFrom(__DIR__.'/resources/views', 'crud');
        AliasLoader::getInstance()->alias('Crud','Icebearsoft\Crud\Crud');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('crud', function($app) {
            return new Crud;
        });
    }
}
