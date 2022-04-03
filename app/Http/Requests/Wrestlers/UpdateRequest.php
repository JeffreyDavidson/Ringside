<?php

namespace App\Http\Requests\Wrestlers;

use App\Models\Wrestler;
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
                'min:3',
                Rule::unique('wrestlers')->ignore($this->route()->parameter('wrestler')->id),
            ],
            'feet' => ['required', 'integer'],
            'inches' => ['required', 'integer', 'max:11'],
            'weight' => ['required', 'integer'],
            'hometown' => ['required', 'string'],
            'signature_move' => ['nullable', 'string'],
            'started_at' => [
                'nullable',
                'string',
                'date',
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
                $wrestler = $this->route()->parameter('wrestler');

                if ($wrestler->isReleased() && ! $wrestler->employedOn($this->date('started_at'))) {
                    $validator->errors()->add(
                        'started_at',
                        "{$wrestler->name} was released and the employment date cannot be changed."
                    );
                }

                if ($wrestler->isCurrentlyEmployed() && ! $wrestler->employedOn($this->date('started_at'))) {
                    $validator->errors()->add(
                        'started_at',
                        "{$wrestler->name} is currently employed and the employment date cannot be changed."
                    );

                    $validator->addFailure('started_at', 'employment_date_cannot_be_changed');
                }
            }
        });
    }
}
