<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;

class Language
{
    public function __construct ( Application $app )
    {
        $this->app = $app;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     *
     * @return mixed
     */
    public function handle ( Request $request , Closure $next )
    {
        // Make sure current locale exists.
        $locale = $request->segment( 1 );

        if ( !array_key_exists( $locale , $this->app->config->get( 'app.locales' ) ) ) {

            $segments = $request->segments();

            array_unshift( $segments , $this->app->config->get( 'app.fallback_locale' ) );

            $this->app->setLocale( $this->app->config->get( 'app.fallback_locale' ) );

            $request->server->set( 'REQUEST_URI' , implode( '/' , $segments ) );

            return $next( $request );
        }
        $this->app->setLocale( $locale );
        return $next( $request );
    }
}
