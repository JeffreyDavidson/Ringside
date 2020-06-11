<?php

namespace App\Http\Requests\TagTeams;

use App\Models\TagTeam;
use App\Rules\ConditionalEmploymentStartDateRule;
use App\Rules\WrestlerCanJoinTagTeamRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('update', TagTeam::class);
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
                Rule::unique('tag_teams')->ignore($this->tag_team->id)
            ],
            'signature_move' => [
                'nullable',
                'string'
            ],
            'started_at' => [
                new ConditionalEmploymentStartDateRule($this->route('tag_team'))
            ],
            'wrestler1' => [
                new WrestlerCanJoinTagTeamRule($this->input('started_at'))
            ],
            'wrestler2' => [
                new WrestlerCanJoinTagTeamRule($this->input('started_at'))
            ]
        ];
    }
}
