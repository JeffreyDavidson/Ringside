<?php

namespace App\Http\Requests\Managers;

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
        $manager = $this->route('manager');

        if (! $this->user()->can('employ', $manager)) {
            return false;
        }

        if (! $manager->canBeEmployed()) {
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
