<?php

declare(strict_types=1);

namespace App\Rules\Users;

use App\Models\Users\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class UniqueEmail implements ValidationRule
{
    public function __construct(private readonly int|string|null $ignoreUserId = null) {}

    /**
     * Compare ignoring case and including soft-deleted users, matching the unique index on lower(email).
     */
    #[\Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $email = Str::lower(mb_trim($value));

        // whereLike only narrows the candidates case-insensitively; the exact comparison happens in PHP so
        // `%` and `_` in the input can never widen the match.
        $taken = User::query()
            ->withTrashed()
            ->whereLike('email', $email, caseSensitive: false)
            ->when($this->ignoreUserId !== null, fn (Builder $query): Builder => $query->whereKeyNot($this->ignoreUserId))
            ->pluck('email')
            ->contains(fn (string $existing): bool => Str::lower($existing) === $email);

        if ($taken) {
            $fail('validation.unique')->translate();
        }
    }
}
