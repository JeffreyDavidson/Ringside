<?php

namespace App\Http\Requests\Stables;

use App\Models\Stable;
use App\Rules\StableHasEnoughMembers;
use App\Rules\TagTeamCanJoinStable;
use App\Rules\WrestlerCanJoinStable;
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
                new WrestlerCanJoinStable(new Stable),
            ],
            'tag_teams.*' => [
                'bail',
                'integer',
                'distinct',
                Rule::exists('tag_teams', 'id'),
                new TagTeamCanJoinStable(new Stable),
            ],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isEmpty()) {
                $membersWereAdded = count($this->input('tag_teams')) > 0 || count($this->input('wrestlers')) > 0;
                if ($membersWereAdded) {
                    $stableHasEnoughMembersResult = (new StableHasEnoughMembers(
                        $this->input('tag_teams'),
                        $this->input('wrestlers')
                    ))->passes();

                    if (! $stableHasEnoughMembersResult) {
                        $validator->addFailure('wrestlers', StableHasEnoughMembers::class);
                        $validator->addFailure('tag_teams', StableHasEnoughMembers::class);
                    }

                    $wrestlerJoinedStableInTagTeamResult = (new WrestlerJoinedStableInTagTeam(
                        $this->input('tag_teams'),
                        $this->input('wrestlers')
                    ))->passes();

                    if (! $wrestlerJoinedStableInTagTeamResult) {
                        $validator->addFailure('wrestlers', WrestlerJoinedStableInTagTeam::class);
                    }
                }
            }
        });
    }
}
