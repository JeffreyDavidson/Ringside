<?php

declare(strict_types=1);

namespace Tests\Fixtures\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractMiddleware
{
    /** @param Closure(Request): Response $next */
    abstract public function handle(Request $request, Closure $next): Response;
}
