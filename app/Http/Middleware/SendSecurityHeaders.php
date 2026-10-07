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
 * choose its own policy. Strict-Transport-Security is sent only on HTTPS
 * requests (behind Cloudflare via the trusted proxy), so local http:// development
 * is never pinned to HTTPS. It covers this host only, without includeSubDomains or
 * preload. Content-Security-Policy is deliberately not set: it needs its own work
 * for Livewire and Vite.
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

    /** HTTPS only for six months (15552000 seconds), this host only. */
    private const string STRICT_TRANSPORT_SECURITY = 'max-age=15552000';

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

        if ($request->isSecure() && ! $response->headers->has('Strict-Transport-Security')) {
            $response->headers->set('Strict-Transport-Security', self::STRICT_TRANSPORT_SECURITY);
        }

        return $response;
    }
}
