<?php

namespace App\Http\Requests\Stables;

use Illuminate\Validation\Rule;
use App\Rules\TagTeamCanJoinStable;
use App\Rules\WrestlerCanJoinStable;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $stable = $this->route('stable');

        return $this->user()->can('update', $stable);
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
                'filled',
                Rule::unique('stables')->ignore($this->route('stable')->id)
            ],
            'started_at' => [
                'sometimes',
                'string',
                'date_format:Y-m-d H:i:s',
                function ($attribute, $value, $fail) {
                    $currentEmployment = $this->route('stable')->currentEmployment ?? null;
                    if ($currentEmployment && optional($currentEmployment->started_at)->isBefore($value)) {
                        $fail(__('validation.before_or_equal', ['attribute' => $attribute, 'date' => $currentEmployment->started_at->toDateTimeString()]));
                    }
                }
            ],
            'wrestlers' => [
                'array'
            ],
            'wrestlers.*' => [
                'bail ',
                'integer',
                Rule::exists('wrestlers', 'id'),
                new WrestlerCanJoinStable($this->route('stable'))
            ],
            'tagteams' => [
                'array'
            ],
            'tagteams.*' => [
                'bail',
                'integer',
                Rule::exists('tag_teams', 'id'),
                new TagTeamCanJoinStable($this->route('stable'))
            ],
        ];
    }

    /**
     * Perform additional validation.
     *
     * @param  Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (is_array($this->wrestlers) && is_array($this->tagteams)) {
                $totalStableMembers = count($this->wrestlers) + (count($this->tagteams) * 2);

                if ($totalStableMembers < 3) {
                    $validator->errors()->add('wrestlers', 'Make sure you have at least 3 members in the stable!');
                    $validator->errors()->add('tagteams', 'Make sure you have at least 3 members in the stable!');
                }
            }
        });
    }
}
