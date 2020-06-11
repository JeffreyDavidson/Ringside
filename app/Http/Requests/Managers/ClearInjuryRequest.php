<?php

namespace App\Http\Requests\Managers;

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
        $manager = $this->route('manager');

        if (! $this->user()->can('clearFromInjury', $manager)) {
            return false;
        }

        if (! $manager->canBeClearedFromInjury()) {
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
