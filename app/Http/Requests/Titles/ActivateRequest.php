<?php

namespace App\Http\Requests\Titles;

use Illuminate\Foundation\Http\FormRequest;

class ActivateRequest extends FormRequest
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

        return $this->user()->can('activate', $title);
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