<?php

namespace App\Http\Requests\Managers;

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
        $manager = $this->route('manager');

        if (! $this->user()->can('reinstate', $manager)) {
            return false;
        }

        if (! $manager->canBeReinstated()) {
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
