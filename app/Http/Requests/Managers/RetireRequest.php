<?php

namespace App\Http\Requests\Managers;

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
        $manager = $this->route('manager');

        if (! $this->user()->can('retire', $manager)) {
            return false;
        }

        if (! $manager->canBeRetired()) {
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
