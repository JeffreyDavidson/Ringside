<?php

namespace App\Http\Requests\Stables;

use App\Models\Stable;
use App\Rules\StableHasEnoughMembers;
use App\Rules\TagTeamCanJoinStable;
use App\Rules\WrestlerCanJoinStable;
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
            'name' => ['required', 'string', Rule::unique('stables', 'name')],
            'started_at' => ['nullable', 'string', 'date_format:Y-m-d H:i:s'],
            'wrestlers' => ['array', new StableHasEnoughMembers($this->input('started_at'), $this->input('tag_teams'))],
            'tag_teams' => ['array'],
            'wrestlers.*' => [
                'bail',
                'integer',
                Rule::exists('wrestlers', 'id'),
                new WrestlerCanJoinStable(new Stable),
            ],
            'tag_teams.*' => [
                'bail',
                'integer',
                Rule::exists('tag_teams', 'id'),
                new TagTeamCanJoinStable(new Stable),
            ],
        ];
    }
}
