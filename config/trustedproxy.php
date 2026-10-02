<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Production is served through Cloudflare, so the TCP peer of a request is a
    | Cloudflare edge address and the real visitor is only in X-Forwarded-For.
    | Trusting these ranges (and only these) lets the framework resolve the real
    | client IP for throttling and logging, while forwarded headers sent by any
    | other peer are ignored.
    |
    | Set TRUSTED_PROXIES to override: a comma-separated list of addresses or
    | CIDR ranges, or `*` when the origin only accepts traffic from the proxy.
    |
    | The default list is Cloudflare's published ranges. Refresh it from
    | https://www.cloudflare.com/ips-v4 and https://www.cloudflare.com/ips-v6
    | when Cloudflare announces a change (last checked 2026-10-02).
    |
    */

    'proxies' => env('TRUSTED_PROXIES') ?? [
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
    ],

];
