<?php

declare(strict_types=1);

namespace App\Rules\Matches;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

class CompetitorsNotDuplicated implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Handle non-array values gracefully
        if (! is_array($value) || $value === []) {
            return;
        }

        $wrestlers = [];
        $tagTeams = [];

        foreach ($value as $side) {
            if (! is_array($side)) {
                continue;
            }

            $wrestlers[] = Arr::wrap(Arr::get($side, 'wrestlers', []));
            $tagTeams[] = Arr::wrap(Arr::get($side, 'tag_teams', []));
        }

        $wrestlers = Arr::flatten($wrestlers);
        $tagTeams = Arr::flatten($tagTeams);

        if (count($wrestlers) !== count(array_unique($wrestlers))) {
            $fail('The same wrestler cannot compete multiple times in this match.');
        }

        if (count($tagTeams) !== count(array_unique($tagTeams))) {
            $fail('The same tag team cannot compete multiple times in this match.');
        }
    }
}
