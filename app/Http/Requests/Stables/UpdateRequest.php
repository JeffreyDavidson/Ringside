<?php

namespace App\Http\Requests\Stables;

use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('update', Stable::class);
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
                Rule::unique('stables')->ignore($this->route()->parameter('stable')->id),
            ],
            'started_at' => [
                'nullable',
                Rule::requiredIf(fn () => ! $this->route()->parameter('stable')->isUnactivated()),
                'string',
                'date',
            ],
            'wrestlers' => [
                'array',
            ],
            'tag_teams' => [
                'array',
            ],
            'wrestlers.*' => [
                'bail ',
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
     * Perform additional validation.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isEmpty()) {
                $stable = $this->route()->parameter('stable');

                if ($this->collect('wrestlers')->isNotEmpty()) {
                    $wrestlerIdsInStable = $stable->currentWrestlers->pluck('id');
                    $wrestlersIdsFromRequest = $this->collect('wrestlers');
                    $wrestlersNewToStable = $wrestlersIdsFromRequest->diff($wrestlerIdsInStable);
                    $wrestlersNewToStable->each(function ($wrestlerId, $key) use ($validator) {
                        $wrestler = Wrestler::with(['currentStable', 'futureEmployment'])
                            ->whereKey($wrestlerId)
                            ->sole();

                        if ($wrestler->isSuspended()) {
                            $validator->errors()->add(
                                'wrestlers.'.$key,
                                "{$wrestler->name} is suspended and cannot join stable."
                            );

                            $validator->addFailure('wrestlers.'.$key, 'cannot_join_stable');
                        }

                        if ($wrestler->isInjured()) {
                            $validator->errors()->add(
                                'wrestlers.'.$key,
                                "{$wrestler->name} is injured and cannot join stable."
                            );

                            $validator->addFailure('wrestlers.'.$key, 'cannot_join_stable');
                        }

                        if ($wrestler->isCurrentlyEmployed()
                            && $this->date('started_at')
                            && ! $wrestler->employedBefore($this->date('started_at'))
                        ) {
                            $validator->errors()->add(
                                'wrestlers.'.$key,
                                "{$wrestler->name} cannot have an employment start date after stable's start date."
                            );

                            $validator->addFailure('wrestlers.'.$key, 'cannot_be_employed_after_stable_start_date');
                        }
                    });
                }

                if ($this->collect('tag_teams')->isNotEmpty()) {
                    $tagTeamIdsInStable = $stable->currentTagTeams->pluck('id');
                    $tagTeamsIdsFromRequest = $this->collect('tag_teams');
                    $tagTeamsNewToStable = $tagTeamsIdsFromRequest->diff($tagTeamIdsInStable);
                    $tagTeamsNewToStable->each(function ($tagTeamId, $key) use ($validator) {
                        $tagTeam = TagTeam::with(['currentStable', 'futureEmployment'])
                            ->whereKey($tagTeamId)
                            ->sole();

                        if ($tagTeam->isSuspended()) {
                            $validator->errors()->add(
                                'tag_teams.'.$key,
                                "{$tagTeam->name} is supsended and cannot join the stable."
                            );

                            $validator->addFailure('tag_teams.'.$key, 'cannot_join_stable');
                        }

                        if ($tagTeam->isCurrentlyEmployed()
                            && $this->date('started_at')
                            && ! $tagTeam->employedBefore($this->date('started_at'))
                        ) {
                            $validator->errors()->add(
                                'tag_teams.'.$key,
                                "{$tagTeam->name} cannot have an employment start date after stable's start date."
                            );

                            $validator->addFailure('tag_teams.'.$key, 'cannot_be_employed_after_stable_start_date');
                        }
                    });
                }

                if ($stable->isCurrentlyActivated()
                    && $this->date('started_at')
                    && ! $stable->activatedOn($this->date('started_at'))
                ) {
                    $validator->errors()->add(
                        'activated_at',
                        "{$stable->name} is currently activated and the activation date cannot be changed."
                    );

                    $validator->addFailure('activated_at', 'activation_date_cannot_be_changed');
                }

                if ($stable->isCurrentlyActivated() || $this->date('activated_at')) {
                    $tagTeamsCountFromRequest = $this->collect('tag_teams')->count();
                    $wrestlersCountFromRequest = $this->collect('wrestlers')->count();

                    $tagTeamMembersCount = $tagTeamsCountFromRequest * 2;

                    if ($tagTeamMembersCount + $wrestlersCountFromRequest < 3) {
                        $validator->errors()->add(
                            '*',
                            "{$stable->name} does not contain at least 3 members."
                        );

                        $validator->addFailure('*', 'not_enough_members');
                    }
                }
            }
        });
    }
}
