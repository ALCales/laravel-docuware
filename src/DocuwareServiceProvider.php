<?php

namespace ALCales\Docuware;

use Illuminate\Support\ServiceProvider;

class DocuwareServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish the config/docuware.php file
        $this->publishes([
            __DIR__.'/../config/docuware.php' => config_path('docuware.php'),
        ], 'config');
    }
}
