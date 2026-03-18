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
         // بنقول للارافيل: لو إحنا مش في بيئة الـ local، اجبر كل الروابط تكون https
         if (!app()->isLocal()) {
             \Illuminate\Support\Facades\URL::forceScheme('https');
         }
     
         // ده حل إضافي لو ريلواي لسه باعت روابط http في بعض الـ Forms
         if (app()->environment('production')) {
             \Illuminate\Support\Facades\URL::forceRootUrl(config('app.url'));
         }
     }
}
