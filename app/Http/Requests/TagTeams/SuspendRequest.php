<?php

namespace App\Http\Requests\TagTeams;

use App\Exceptions\CannotBeSuspendedException;
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
        $tagTeam = $this->route('tag_team');

        if (! $this->user()->can('suspend', $tagTeam)) {
            return false;
        }

        if (! $tagTeam->canBeSuspended()) {
            throw new CannotBeSuspendedException();
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
