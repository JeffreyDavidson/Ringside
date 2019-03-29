<?php

namespace App\Http\Requests;

use App\Models\Stable;
use App\Rules\TagTeamCanJoinStable;
use App\Rules\WrestlerCanJoinStable;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStableRequest extends FormRequest
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
            'name' => ['required'],
            'started_at' => ['required', 'date_format:Y-m-d H:i:s'],
            'wrestlers' =>  ['array'],
            'wrestlers.*'  => ['bail ', 'integer', 'exists:wrestlers,id' , new WrestlerCanJoinStable($this->route('stable'))],
            'tagteams' =>  ['array'],
            'tagteams.*' => ['bail', 'integer', 'exists:tag_teams,id' , new TagTeamCanJoinStable($this->route('stable'))],
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
