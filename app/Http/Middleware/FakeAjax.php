<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FakeAjax
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->query->has('ajax') || $request->query->has('draw')) {
            $request->query->remove('ajax');
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        }

        return $next($request);
    }
}
