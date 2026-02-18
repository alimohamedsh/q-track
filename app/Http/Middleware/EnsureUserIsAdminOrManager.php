<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdminOrManager
{
    /**
     * السماح للمسؤول ومدير الفنيين فقط بدخول لوحة الإدارة.
     * الفنيون يُحوَّلون إلى لوحة الفني.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('filament.admin.auth.login');
        }

        if ($user->hasRole('admin') || $user->hasRole('manager')) {
            return $next($request);
        }

        return redirect()->route('technician.index');
    }
}
