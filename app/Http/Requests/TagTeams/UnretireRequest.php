<?php

namespace App\Http\Requests\TagTeams;

use App\Exceptions\CannotBeUnretiredException;
use Illuminate\Foundation\Http\FormRequest;

class UnretireRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $tagTeam = $this->route('tag_team');

        if (! $this->user()->can('unretire', $tagTeam)) {
            return false;
        }

        if (! $tagTeam->canBeUnretired()) {
            throw new CannotBeUnretiredException();
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
