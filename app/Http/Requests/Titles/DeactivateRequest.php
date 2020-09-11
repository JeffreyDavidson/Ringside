<?php

namespace App\Http\Requests\Titles;

use Illuminate\Foundation\Http\FormRequest;

class DeactivateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        /** @var \App\Models\Title */
        $title = $this->route('title');

        return $this->user()->can('deactivate', $title);
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
