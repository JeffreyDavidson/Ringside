<?php

namespace App\Http\Requests\Stables;

use Illuminate\Foundation\Http\FormRequest;

class DisbandRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        /** @var \App\Models\Stable */
        $stable = $this->route('stable');

        return $this->user()->can('disband', $stable);
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