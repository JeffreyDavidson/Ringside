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
        $title = $this->route('title');

        if ($this->user()->can('activate', $title)) {
            return true;
        }

        return false;
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
