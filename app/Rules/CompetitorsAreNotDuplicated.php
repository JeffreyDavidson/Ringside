<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

class CompetitorsAreNotDuplicated implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $wrestlers = [];
        $tagTeams = [];
        $matchCompetitors = (array) $value;

        foreach ($matchCompetitors as $competitors) {
            /** @var array<'wrestlers'|'tagteams', \App\Models\Wrestler|\App\Models\TagTeam> $competitors */
            if (Arr::has($competitors, 'wrestlers')) {
                $wrestlers[] = $competitors['wrestlers'];
            }

            if (Arr::has($competitors, 'tagteams')) {
                $tagTeams[] = $competitors['tagteams'];
            }
        }

        $wrestlers = Arr::flatten($wrestlers);
        $tagTeams = Arr::flatten($tagTeams);

        if (count($wrestlers) !== count(array_unique($wrestlers))) {
            $fail('The same wrestler is duplicated in this match.');
        }

        if (count($tagTeams) !== count(array_unique($tagTeams))) {
            $fail('The same tag team is duplicated in this match.');
        }
    }
}
