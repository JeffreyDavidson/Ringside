<?php

declare(strict_types=1);

namespace App\Support\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Looks users up by email ignoring case, so accounts stored with a mixed-case address can still sign in
 * and reset their password. Used by the guard and the password broker.
 */
class CaseInsensitiveEmailUserProvider extends EloquentUserProvider
{
    /** @param  array<string, mixed>  $credentials */
    #[\Override]
    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials)
    {
        if (isset($credentials['email']) && is_string($credentials['email'])) {
            $email = Str::lower(mb_trim($credentials['email']));

            $credentials['email'] = fn (Builder $query): Builder => $query->whereRaw('lower(email) = ?', [$email]);
        }

        return parent::retrieveByCredentials($credentials);
    }
}
