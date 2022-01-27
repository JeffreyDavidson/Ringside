<?php

namespace App\Http\Requests\Managers;

use App\Models\Manager;
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
        return $this->user()->can('update', Manager::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'first_name' => ['required', 'string', 'min:3'],
            'last_name' => ['required', 'string', 'min:3'],
            'started_at' => [
                'nullable',
                'string',
                'date',
            ],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     *
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isEmpty()) {
                /** @var \App\Models\Manager $manager */
                $manager = $this->route()->parameter('manager');

                if ($manager->isCurrentlyEmployed()) {
                    $validator->errors()->add(
                        'started_at',
                        "{$manager->full_name} is currently employed and cannot have their original start date changed."
                    );
                    $validator->addFailure('started_at', 'employment_date_cannot_be_changed');
                }

                if ($manager->isReleased()) {
                    $validator->errors()->add(
                        'started_at',
                        "{$manager->full_name} has been released and cannot have their original start date changed."
                    );
                    $validator->addFailure('started_at', 'employment_date_cannot_be_changed');
                }

                if ($manager->isRetired()) {
                    $validator->errors()->add(
                        'started_at',
                        "{$manager->full_name} is currently retired and cannot have their original start date changed."
                    );
                    $validator->addFailure('started_at', 'employment_date_cannot_be_changed');
                }
            }
        });
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'started_at' => 'started at',
        ];
    }
}
