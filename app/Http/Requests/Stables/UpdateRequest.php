<?php

namespace App\Http\Requests\Stables;

use App\Rules\ActivationStartDateCanBeChanged;
use App\Rules\TagTeamCanJoinStable;
use App\Rules\WrestlerCanJoinStable;
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
        /** @var \App\Models\Stable */
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
            'name' => ['filled', Rule::unique('stables')->ignore($this->route('stable')->id)],
            'started_at' => ['nullable', 'string', 'date_format:Y-m-d H:i:s', new ActivationStartDateCanBeChanged($this->route('stable'))],
            'wrestlers' => ['array'],
            'wrestlers.*' => ['bail ', 'integer', Rule::exists('wrestlers', 'id'), new WrestlerCanJoinStable($this->route('stable'))],
            'tag_teams' => ['array'],
            'tag_teams.*' => ['bail', 'integer', Rule::exists('tag_teams', 'id'), new TagTeamCanJoinStable($this->route('stable'))],
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
