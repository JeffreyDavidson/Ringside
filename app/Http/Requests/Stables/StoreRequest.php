<?php

namespace App\Http\Requests\Stables;

use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Rules\WrestlerJoinedStableInTagTeam;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('create', Stable::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'min:3',
                Rule::unique('stables', 'name'),
            ],
            'started_at' => [
                'nullable',
                'string',
                'date',
            ],
            'wrestlers' => ['array'],
            'tag_teams' => ['array'],
            'wrestlers.*' => [
                'bail',
                'integer',
                'distinct',
                Rule::exists('wrestlers', 'id'),
            ],
            'tag_teams.*' => [
                'bail',
                'integer',
                'distinct',
                Rule::exists('tag_teams', 'id'),
            ],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     *
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isEmpty()) {
                if ($this->requestHasMembers() || $this->date('started_at')) {
                    $tagTeamIds = $this->collect('tag_teams');
                    $wrestlerIds = $this->collect('wrestlers');

                    if ($tagTeamIds->count() * 2 + $wrestlerIds->count() < 3) {
                        $validator->errors()->add(
                            '*',
                            'Stable must does not contain at least 3 members.'
                        );

                        $validator->addFailure('*', 'not_enough_members');
                    }

                    if ($this->collect('wrestlers')->isNotEmpty()) {
                        $this->collect('wrestlers')->each(function ($wrestlerId, $key) use ($validator) {
                            $wrestler = Wrestler::with('currentStable')->whereKey($wrestlerId)->sole();

                            if ($wrestler->currentStable !== null && $wrestler->currentStable->exists()) {
                                $validator->errors()->add(
                                    'wrestlers.'.$key,
                                    "{$wrestler->name} is already a member of a stable."
                                );

                                $validator->addFailure('wrestlers.'.$key, 'wrestler_already_in_different_stable');
                            }
                        });
                    }

                    if ($tagTeamIds->isNotEmpty()) {
                        $tagTeamIds->each(function ($tagTeamId, $key) use ($validator) {
                            $tagTeam = TagTeam::with('currentWrestlers')->whereKey($tagTeamId)->sole();

                            if ($tagTeam->currentStable !== null) {
                                $validator->errors()->add(
                                    'tag_teams.'.$key,
                                    "{$tagTeam->name} is already a member of a stable."
                                );

                                $validator->addFailure('tag_teams.'.$key, 'tag_team_already_in_different_stable');
                            }

                            $wrestlersFromRequest = $this->collect('wrestlers'); // 1, 2, 3
                            $tagTeamPartnerIds = $tagTeam->currentWrestlers->pluck('id'); // 3, 4

                            $wrestlersInTagTeam = $tagTeamPartnerIds->intersect($wrestlersFromRequest);

                            if ($wrestlersInTagTeam->isNotEmpty()) {
                                $validator->errors()->add(
                                    'wrestlers',
                                    'There are wrestlers that are added to the stable that were added from a tag team.'
                                );

                                $validator->addFailure('wrestlers', 'wrestlers_added_that_are_inside_tag_teams');
                            }
                        });
                    }
                }
            }
        });
    }

    protected function requestHasMembers()
    {
        return $this->collect('tag_teams')->isNotEmpty() || $this->collect('wrestlers')->isNotEmpty();
    }
}
