<?php

namespace App\Http\Requests\Referees;

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
        $referee = $this->route('referee');

        if (! $this->user()->can('unretire', $referee)) {
            return false;
        }

        if (! $referee->canBeUnretired()) {
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
