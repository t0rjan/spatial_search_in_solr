<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Solarium\Client;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot ()
    {
        $this->app->bind( 'Solarium\Client' , function ( $app ) {
            return new Client( $app['config']['solarium'] );
        } );
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment() !== 'production') {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }
        // ...
    }
}
