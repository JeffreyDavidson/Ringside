<?php

namespace App\Http\Requests\TagTeams;

use Illuminate\Foundation\Http\FormRequest;

class SuspendRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        /** @var \App\Models\TagTeam */
        $tagTeam = $this->route('tag_team');

        return $this->user()->can('suspend', $tagTeam);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [];
    }
}
