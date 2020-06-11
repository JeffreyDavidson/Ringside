<?php

namespace App\Http\Requests\Referees;

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
        $referee = $this->route('referee');

        if (! $this->user()->can('suspend', $referee)) {
            return false;
        }

        if (! $referee->canBeSuspended()) {
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
