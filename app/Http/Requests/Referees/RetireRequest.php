<?php

namespace App\Http\Requests\Referees;

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
        $referee = $this->route('referee');

        if (! $this->user()->can('retire', $referee)) {
            return false;
        }

        if (! $referee->canBeRetired()) {
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
