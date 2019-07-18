<?php

namespace App\Http\Requests;

use App\Rules\CanJoinTagTeam;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTagTeamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $tagteam = $this->route('tagteam');

        return $this->user()->can('update', $tagteam);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['nullable', 'string', Rule::unique('tag_teams')->ignore($this->tagteam->id)],
            'signature_move' => ['nullable', 'string'],
            'started_at' => ['nullable', 'string', 'date_format:Y-m-d H:i:s'],
            'wrestlers' => ['required', 'array', 'size:2'],
            'wrestlers.*' => ['bail', 'integer', 'exists:wrestlers,id', new CanJoinTagTeam],
        ];
    }
}
