<?php

namespace App\Http\Requests\Wrestlers;

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
        $wrestler = $this->route('wrestler');

        if (! $this->user()->can('clearFromInjury', $wrestler)) {
            return false;
        }

        if (! $wrestler->canBeClearedFromInjury()) {
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
