<?php

namespace App\Http\Requests\Wrestlers;

use App\Exceptions\CannotBeEmployedException;
use Illuminate\Foundation\Http\FormRequest;

class EmployRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $wrestler = $this->route('wrestler');

        if (! $this->user()->can('employ', $wrestler)) {
            return false;
        }

        if (! $wrestler->canBeEmployed()) {
            throw new CannotBeEmployedException();
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
