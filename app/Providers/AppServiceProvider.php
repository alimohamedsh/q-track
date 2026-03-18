<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
   
     public function boot(): void
     {
         if (str_contains(request()->getHost(), 'lhr.life')) {
             \Illuminate\Support\Facades\URL::forceRootUrl(config('app.url'));
             \Illuminate\Support\Facades\URL::forceScheme('https');
         }
     }
}
