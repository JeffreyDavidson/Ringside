<?php

declare(strict_types=1);

namespace App\Rules\Wrestlers;

use App\Models\Roster\Wrestlers\Wrestler;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsNotInjured implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_int($value) && ! is_string($value)) {
            $fail('The selected wrestler is invalid.');

            return;
        }

        $wrestler = Wrestler::query()->whereKey($value)->first();

        if (! $wrestler instanceof Wrestler) {
            $fail('The selected wrestler is invalid.');

            return;
        }

        if ($wrestler->currentInjury()->exists()) {
            $fail("{$wrestler->name} is injured and cannot join the stable.");
        }
    }
}
