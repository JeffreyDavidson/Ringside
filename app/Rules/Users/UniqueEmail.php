<?php

declare(strict_types=1);

namespace App\Rules\Users;

use App\Models\Users\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
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

        $taken = User::query()
            ->withTrashed()
            ->whereRaw('lower(email) = ?', [Str::lower(mb_trim($value))])
            ->when($this->ignoreUserId !== null, fn ($query) => $query->whereKeyNot($this->ignoreUserId))
            ->exists();

        if ($taken) {
            $fail('validation.unique')->translate();
        }
    }
}
