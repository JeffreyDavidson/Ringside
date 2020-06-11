<?php

namespace App\Http\Requests\TagTeams;

use App\Exceptions\CannotBeReinstatedException;
use Illuminate\Foundation\Http\FormRequest;

class ReinstateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $tagTeam = $this->route('tag_team');

        if (! $this->user()->can('reinstate', $tagTeam)) {
            return false;
        }

        if (! $tagTeam->canBeReinstated()) {
            throw new CannotBeReinstatedException();
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
