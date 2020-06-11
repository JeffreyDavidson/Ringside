<?php

namespace App\Http\Requests\Stables;

use App\Models\Stable;
use Illuminate\Validation\Rule;
use App\Rules\TagTeamCanJoinStable;
use App\Rules\WrestlerCanJoinStable;
use Illuminate\Foundation\Http\FormRequest;

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
                Rule::unique('stables', 'name')
            ],
            'started_at' => [
                'nullable',
                'string',
                'date_format:Y-m-d H:i:s'
            ],
            'wrestlers' => [
                'array',
                Rule::requiredIf(fn () => count($this->tagteams) <= 1)
            ],
            'tagteams' => [
                'array',
                Rule::requiredIf(fn () => count($this->wrestlers) <= 2),
            ],
            'wrestlers.*' => [
                'bail',
                'integer',
                Rule::exists('wrestlers', 'id'),
                new WrestlerCanJoinStable(new Stable)
            ],
            'tagteams.*' => [
                'bail',
                'integer',
                Rule::exists('tag_teams', 'id'),
                new TagTeamCanJoinStable(new Stable)
            ],
        ];
    }

    /**
     * Undocumented function
     *
     * @param  Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     */
    public function after($validator)
    {
        $totalStableMembers = count($this->wrestlers) + (count($this->tagteams) * 2);

        if ($totalStableMembers < 3) {
            $validator->errors()->add('wrestlers', 'Make sure you have at least 3 members in the stable!');
            $validator->errors()->add('tagteams', 'Make sure you have at least 3 members in the stable!');
        }
    }
}
