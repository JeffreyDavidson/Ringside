<?php

declare(strict_types=1);

namespace App\Rules\Events;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A cue's track reference may be a link, but never one that carries a user name or password. The scheme and host are
 * left to the `url:https` rule.
 */
class HttpsUrlWithoutCredentials implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $url = parse_url($value);

        if ($url === false || isset($url['user']) || isset($url['pass'])) {
            $fail(__('production.invalid_cue'));
        }
    }
}
