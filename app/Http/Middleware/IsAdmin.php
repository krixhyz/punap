<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        if (!$request->user()->isAdmin()) {
            abort(403, 'Admins only');
        }

        return $next($request);
    }
}