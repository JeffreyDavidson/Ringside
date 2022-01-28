<?php

namespace App\Rules;

use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Contracts\Validation\Rule;

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
     *
     * @return bool
     */
    public function passes($attribute, $value)
    {
        foreach ($value as $sideNumber => $sideCompetitors) {
            $wrestlers = array_filter(
                $sideCompetitors,
                static fn ($contestant) => $contestant['competitor_type'] === 'wrestler'
            );

            $tagTeams = array_filter(
                $sideCompetitors,
                static fn ($contestant) => $contestant['competitor_type'] === 'tag_team'
            );

            $wrestler_ids = array_column($wrestlers, 'competitor_id');
            $tag_team_ids = array_column($tagTeams, 'competitor_id');

            $existing_wrestler_ids = Wrestler::whereIn('id', $wrestler_ids)->pluck('id')->toArray();
            $existing_tag_team_ids = TagTeam::whereIn('id', $tag_team_ids)->pluck('id')->toArray();

            $diffWrestlers = array_diff($wrestler_ids, $existing_wrestler_ids);
            $diffTagTeams = array_diff($tag_team_ids, $existing_tag_team_ids);
        }

        if (count($diffWrestlers) > 0) {
            $this->setMessage('There are wrestlers added to the match that don\'t exist in the database.');

            return false;
        }

        if (count($diffTagTeams) > 0) {
            $this->setMessage('There are tag teams added to the match that don\'t exist in the database.');

            return false;
        }

        return true;
    }

    /**
     * Set the message of the error message.
     *
     * @param  string $message
     *
     * @return void
     */
    protected function setMessage(string $message)
    {
        $this->message = $message;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The validation error message.';
    }
}
