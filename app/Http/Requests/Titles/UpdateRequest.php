<?php

namespace App\Http\Requests\Titles;

use App\Policies\TitlePolicy;
use App\Rules\ConditionalActivationStartDateRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    public function authorize()
    {
        if (! Auth::check()) {
            return false;
        }

        return $this->user()->can('update', Title::class);
    }

    public function rules()
    {
        return [
            'name' => [
                'required',
                'min:3',
                'ends_with:Title,Titles',
                Rule::unique('titles')->ignore($this->title->id)
            ],
            'activated_at' => [
                new ConditionalActivationStartDateRule($this->route('title'))
            ],
        ];
    }
}
