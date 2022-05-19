<?php

declare(strict_types=1);

namespace App\Http\Requests\Referees;

use App\Models\Referee;
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
        return $this->user()->can('update', Referee::class);
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
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isEmpty()) {
                $referee = $this->route()->parameter('referee');

                if ($referee->isCurrentlyEmployed()) {
                    $validator->errors()->add(
                        'started_at',
                        "{$referee->full_name} is currently employed and cannot have their original start date changed."
                    );
                    $validator->addFailure('started_at', 'employment_date_cannot_be_changed');
                }

                if ($referee->isReleased()) {
                    $validator->errors()->add(
                        'started_at',
                        "{$referee->full_name} has been released and cannot have their original start date changed."
                    );
                    $validator->addFailure('started_at', 'employment_date_cannot_be_changed');
                }

                if ($referee->isRetired()) {
                    $validator->errors()->add(
                        'started_at',
                        "{$referee->full_name} is currently retired and cannot have their original start date changed."
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
