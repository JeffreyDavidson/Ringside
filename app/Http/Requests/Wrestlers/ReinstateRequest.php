<?php

namespace App\Http\Requests\Wrestlers;

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
        /** @var \App\Models\Wrestler */
        $wrestler = $this->route('wrestler');

        return $this->user()->can('reinstate', $wrestler);
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
