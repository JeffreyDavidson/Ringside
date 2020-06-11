<?php

namespace App\Http\Requests\Wrestlers;

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
        $wrestler = $this->route('wrestler');

        if (! $this->user()->can('reinstate', $wrestler)) {
            return false;
        }

        if (! $wrestler->canBeReinstated()) {
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
