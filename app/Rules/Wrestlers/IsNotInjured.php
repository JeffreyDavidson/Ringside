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
        $wrestler = Wrestler::query()->findOrFail($value);

        if ($wrestler->isInjured()) {
            $fail("{$wrestler->name} is injured and cannot join the stable.");
        }
    }
}
