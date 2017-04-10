<?php

namespace Karogis\GeoServer;

use Illuminate\Support\ServiceProvider;

class GeoServerServiceProvider extends ServiceProvider
{

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['geoserver'];
    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $configFile = __DIR__ . '/../config/geoserver.php';

        $this->mergeConfigFrom($configFile, 'geoserver');

        $this->publishes([
            $configFile => config_path('geoserver.php')
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('geoserver', function ($app) {
            return new GeoServer();
        });
    }
}
