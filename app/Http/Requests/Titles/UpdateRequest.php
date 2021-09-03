<?php

namespace App\Http\Requests\Titles;

use App\Models\Title;
use App\Rules\ActivationStartDateCanBeChanged;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('update', Title::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => [
                'required',
                'min:3',
                'ends_with:Title,Titles',
                Rule::unique('titles')->ignore($this->route('title')->id),
            ],
            'activated_at' => [
                'nullable',
                'string',
                'date_format:Y-m-d H:i:s',
                new ActivationStartDateCanBeChanged($this->route('title')),
            ],
        ];
    }
}
