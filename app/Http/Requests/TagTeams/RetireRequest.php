<?php

namespace App\Http\Requests\TagTeams;

use App\Exceptions\CannotBeRetiredException;
use Illuminate\Foundation\Http\FormRequest;

class RetireRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $tagTeam = $this->route('tag_team');

        if (! $this->user()->can('retire', $tagTeam)) {
            return false;
        }

        if (! $tagTeam->canBeRetired()) {
            throw new CannotBeRetiredException();
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
