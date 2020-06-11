<?php

namespace App\Http\Requests\Managers;

use App\Exceptions\CannotBeInjuredException;
use Illuminate\Foundation\Http\FormRequest;

class InjureRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $manager = $this->route('manager');

        if (! $this->user()->can('injure', $manager)) {
            return false;
        }

        if (! $manager->canBeInjured()) {
            throw new CannotBeInjuredException();
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
