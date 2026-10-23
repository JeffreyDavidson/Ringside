<?php

declare(strict_types=1);

namespace App\Rules\Events;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PublicTicketUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $url = is_string($value) ? parse_url($value) : false;
        $host = is_array($url) ? ($url['host'] ?? '') : '';
        $ip = trim($host, '[]');

        if (! is_array($url) || strtolower($url['scheme'] ?? '') !== 'https' || $host === '' || isset($url['user']) || isset($url['pass'])
            || preg_match('/[\x00-\x20\x7f]/', (string) $value) === 1 || ! str_contains($host, '.')
            || preg_match('/(?:^|\.)(localhost|local|test|internal)$/i', $host) === 1
            || (filter_var($ip, FILTER_VALIDATE_IP) !== false && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false)) {
            $fail(__('public-events.ticket_invalid'));
        }
    }
}
