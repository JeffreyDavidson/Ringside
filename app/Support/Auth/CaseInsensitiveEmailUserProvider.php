<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Models\Users\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Looks users up by email ignoring case, so accounts stored with a mixed-case address can still sign in
 * and reset their password. Used by the guard and the password broker.
 */
class CaseInsensitiveEmailUserProvider extends EloquentUserProvider
{
    /** @param  array<string, mixed>  $credentials */
    #[\Override]
    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials): ?Authenticatable
    {
        if (! isset($credentials['email']) || ! is_string($credentials['email'])) {
            return parent::retrieveByCredentials($credentials);
        }

        $email = Str::lower(mb_trim($credentials['email']));

        // whereLike only narrows the candidates case-insensitively; the exact comparison happens in PHP so
        // `%` and `_` in the input can never widen the match.
        $user = $this->newModelQuery()
            ->where(Arr::except($credentials, ['email', 'password']))
            ->whereLike('email', $email, caseSensitive: false)
            ->get()
            ->first(fn (Model $candidate): bool => $candidate instanceof User && Str::lower($candidate->email) === $email);

        return $user instanceof User ? $user : null;
    }
}
