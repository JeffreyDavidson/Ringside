<?php

namespace App\Http\Requests\Wrestlers;

use App\Models\Wrestler;
use App\Rules\ConditionalEmploymentStartDateRule;
use Illuminate\Foundation\Http\FormRequest;
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
        return $this->user()->can('update', Wrestler::class);
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
                'min:3'
            ],
            'feet' => [
                'required',
                'integer',
                'min:5',
                'max:7'
            ],
            'inches' => [
                'required',
                'integer',
                'max:11'
            ],
            'weight' => [
                'required',
                'integer'
            ],
            'hometown' => [
                'required',
                'string'
            ],
            'signature_move' => [
                'nullable',
                'string'
            ],
            'started_at' => [
                new ConditionalEmploymentStartDateRule($this->route('wrestler'))
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'started_at' => 'date started',
            'signature_move' => 'signature move',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isEmpty()) {
                $this->merge(['height' => ($this->input('feet') * 12) + $this->input('inches')]);
                $this->offsetUnset('feet');
                $this->offsetUnset('inches');
            }
        });
    }
}
