<?php

namespace App\Http\Requests\TagTeams;

use App\Exceptions\CannotBeEmployedException;
use Illuminate\Foundation\Http\FormRequest;

class EmployRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $tagTeam = $this->route('tag_team');

        if (! $this->user()->can('employ', $tagTeam)) {
            return false;
        }

        if (! $tagTeam->canBeEmployed()) {
            throw new CannotBeEmployedException();
        }

        return true;
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
