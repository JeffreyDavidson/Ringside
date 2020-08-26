<?php

namespace App\Http\Requests\TagTeams;

use App\Models\TagTeam;
use App\Rules\WrestlerCanJoinTagTeamRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
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
        if (! Auth::check()) {
            return false;
        }

        return $this->user()->can('create', TagTeam::class);
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
                Rule::unique('tag_teams'),
            ],
            'signature_move' => [
                'nullable',
                'string',
            ],
            'started_at' => [
                'nullable',
                'string',
                'date_format:Y-m-d H:i:s',
            ],
            'wrestler1' => [
                'nullable',
                'bail',
                'integer',
                'different:wrestler2',
                Rule::exists('wrestlers', 'id'),
                new WrestlerCanJoinTagTeamRule($this->input('started_at')),
            ],
            'wrestler2' => [
                'nullable',
                'bail',
                'integer',
                'different:wrestler1',
                Rule::exists('wrestlers', 'id'),
                new WrestlerCanJoinTagTeamRule($this->input('started_at')),
             ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'wrestler1.different' => 'Tag team partners cannot be the same wrestler.',
            'wrestler2.different' => 'Tag team partners cannot be the same wrestler.',
        ];
    }
}
