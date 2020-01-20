<?php

namespace App\Http\Requests\Referees;

use App\Models\Referee;
use Illuminate\Foundation\Http\FormRequest;

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
        $rules = [
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'started_at' => ['string', 'date_format:Y-m-d H:i:s']
        ];

        if ($this->referee->currentEmployment) {
            if ($this->referee->currentEmployment->started_at) {
                $rules['started_at'][] = 'required';
            }

            if ($this->referee->currentEmployment->started_at && $this->referee->currentEmployment->started_at->isPast()) {
                $rules['started_at'][] = 'before_or_equal:' . $this->referee->currentEmployment->started_at->toDateTimeString();
            }
        }

        return $rules;
    }
}