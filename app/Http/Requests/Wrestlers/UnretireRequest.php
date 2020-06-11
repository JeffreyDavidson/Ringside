<?php

namespace App\Http\Requests\Wrestlers;

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
        $wrestler = $this->route('wrestler');

        if (! $this->user()->can('unretire', $wrestler)) {
            return false;
        }

        if (! $wrestler->canBeUnretired()) {
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
