<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds the browser security headers the web server does not send.
 *
 * A header that is already on the response is left untouched, so a route can
 * choose its own policy. Content-Security-Policy and Strict-Transport-Security
 * are deliberately not set here: CSP needs its own work for Livewire and Vite,
 * and HSTS is configured at Cloudflare.
 */
class SendSecurityHeaders
{
    /**
     * @var array<string, string>
     */
    private const array HEADERS = [
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'accelerometer=(), autoplay=(), camera=(), display-capture=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), midi=(), payment=(), usb=()',
        'X-Content-Type-Options' => 'nosniff',
    ];

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach (self::HEADERS as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }
}
