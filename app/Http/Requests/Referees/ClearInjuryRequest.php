<?php

namespace App\Http\Requests\Referees;

use App\Exceptions\CannotBeClearedFromInjuryException;
use Illuminate\Foundation\Http\FormRequest;

class ClearInjuryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $referee = $this->route('referee');

        if (! $this->user()->can('clearFromInjury', $referee)) {
            return false;
        }

        if (! $referee->canBeClearedFromInjury()) {
            throw new CannotBeClearedFromInjuryException();
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
