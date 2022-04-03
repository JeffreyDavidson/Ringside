<?php

namespace App\Http\Requests\Titles;

use App\Models\Title;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
                'string',
                'min:3',
                'ends_with:Title,Titles',
                Rule::unique('titles')->ignore($this->route()->parameter('title')->id),
            ],
            'activated_at' => [
                'nullable',
                'string',
                'date',
            ],
        ];
    }

    /**
     * Perform additional validation.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isEmpty()) {
                $title = $this->route()->parameter('title');

                if ($title->isCurrentlyActivated() && ! $title->activatedOn($this->date('activated_at'))) {
                    $validator->errors()->add(
                        'activated_at',
                        "{$title->name} is currently activated and the activation date cannot be changed."
                    );

                    $validator->addFailure('activated_at', 'activation_date_cannot_be_changed');
                }
            }
        });
    }
}
