<?php

namespace App\Http\Requests\Wrestlers;

use App\Exceptions\CannotBeReleasedException;
use Illuminate\Foundation\Http\FormRequest;

class ReleaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $wrestler = $this->route('wrestler');

        if (! $this->user()->can('release', $wrestler)) {
            return false;
        }

        if (! $wrestler->canBeReleased()) {
            throw new CannotBeReleasedException();
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
