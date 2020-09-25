<?php

namespace App\Http\Requests\TagTeams;

use App\Models\TagTeam;
use App\Rules\WrestlerCanJoinTagTeamRule;
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
            'name' => ['required', 'string', Rule::unique('tag_teams', 'name')],
            'signature_move' => ['nullable', 'string'],
            'started_at' => ['nullable', 'string', 'date_format:Y-m-d H:i:s'],
            'wrestlers' => ['nullable', 'array'],
            'wrestlers.*' => ['nullable', 'bail', 'integer', 'distinct', Rule::exists('wrestlers', 'id'), new WrestlerCanJoinTagTeamRule($this->input('started_at'))],
        ];
    }
}
