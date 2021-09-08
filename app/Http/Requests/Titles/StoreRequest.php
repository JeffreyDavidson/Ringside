<?php

namespace App\Http\Requests\Titles;

use App\Models\Title;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('create', Title::class);
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'min:3', 'ends_with:Title,Titles', Rule::unique('titles', 'name')],
            'activated_at' => ['nullable', 'string', 'date'],
        ];
    }
}
