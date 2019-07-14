<?php

namespace App\Http\Requests;

use App\Models\Title;
use Illuminate\Foundation\Http\FormRequest;

class StoreTitleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('create', Title::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'min:3', 'ends_with:Title, Titles', 'unique:titles,name'],
            'introduced_at' => ['required', 'date_format:Y-m-d H:i:s'],
        ];
    }
}
