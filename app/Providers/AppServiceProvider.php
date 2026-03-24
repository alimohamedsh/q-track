<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        if (! app()->isLocal()) {
            URL::forceScheme('https');
        }

        // Production: اضبط جذر الروابط على الـ origin فقط (scheme + host [+ port]).
        // لو APP_URL على Railway فيه path زائد أو دومين متكرر، Filament/redirects تولّد URLs مكسورة
        // (مثل /your-domain.up.railway.app/your-domain.../admin/login → 404).
        if (app()->environment('production')) {
            $raw = config('app.url');
            if (is_string($raw) && $raw !== '') {
                $parts = parse_url($raw);
                if (is_array($parts) && isset($parts['scheme'], $parts['host'])) {
                    $root = $parts['scheme'].'://'.$parts['host'];
                    if (! empty($parts['port'])) {
                        $root .= ':'.$parts['port'];
                    }
                    URL::forceRootUrl($root);
                }
            }
        }
    }
}
