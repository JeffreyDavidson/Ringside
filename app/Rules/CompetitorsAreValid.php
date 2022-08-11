<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;

class CompetitorsAreValid implements Rule
{
    /**
     * The message to be sent as the validation message.
     *
     * @var string
     */
    protected string $message;

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  array  $value
     * @return bool
     */
    public function passes($attribute, $value)
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
            $this->message = 'There are wrestlers added to the match that don\'t exist in the database.';

            return false;
        }

        if (count($diffTagTeams) > 0) {
            $this->message = 'There are tag teams added to the match that don\'t exist in the database.';

            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->message;
    }
}
