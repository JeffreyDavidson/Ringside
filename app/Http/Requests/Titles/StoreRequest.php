<?php

namespace App\Http\Requests\Titles;

use App\Models\Title;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('create', Title::class);
    }

    public function rules()
    {
        return [
            'name' => [
                'required',
                'min:3',
                'ends_with:Title,Titles',
                Rule::unique('titles', 'name')
            ],
            'activated_at' => [
                'nullable',
                'string',
                'date_format:Y-m-d H:i:s'
            ],
        ];
    }

    public function messages()
    {
        return [
            'activated_at.date_format' => 'The :attribute must be in the format of YYYY-MM-DD HH::MM:SS',
        ];
    }
}
