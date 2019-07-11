<?php

namespace App\Http\Requests;

use App\Models\Wrestler;
use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreWrestlerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('create', Wrestler::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'min:3'],
            'feet' => ['required', 'numeric', 'min:5', 'max:7'],
            'inches' => ['required', 'numeric', 'max:11'],
            'weight' => ['required', 'numeric'],
            'hometown' => ['required', 'string'],
            'signature_move' => ['nullable', 'string'],
            'hired_at' => ['required', 'string', 'date'],
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
            'hired_at' => 'date hired',
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
