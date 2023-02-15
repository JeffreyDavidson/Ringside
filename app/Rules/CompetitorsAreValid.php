<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\TagTeam;
use App\Models\Wrestler;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

class CompetitorsAreValid implements ValidationRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $diffWrestlers = [];
        $diffTagTeams = [];

        foreach ($value as $sideCompetitors) {
            $wrestlers = Arr::get($sideCompetitors, 'wrestlers', []);
            $tagTeams = Arr::get($sideCompetitors, 'tag_teams', []);

            $existing_wrestler_ids = Wrestler::whereIn('id', $wrestlers)->pluck('id')->toArray();
            $existing_tag_team_ids = TagTeam::whereIn('id', $tagTeams)->pluck('id')->toArray();

            $diffWrestlers = array_diff($wrestlers, $existing_wrestler_ids);
            $diffTagTeams = array_diff($tagTeams, $existing_tag_team_ids);
        }

        if (count($diffWrestlers) > 0) {
            $fail('There are wrestlers added to the match that don\'t exist in the database.');
        }

        if (count($diffTagTeams) > 0) {
            $fail('There are tag teams added to the match that don\'t exist in the database.');
        }
    }
}
