<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRefereeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $referee = $this->route('referee');

        return $this->user()->can('update', $referee);
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

        if ($this->referee->employment) {
            if ($this->referee->employment->started_at) {
                $rules['started_at'][] = 'required';
            }

            if ($this->referee->employment->started_at && $this->referee->employment->started_at->isPast()) {
                $rules['started_at'][] = 'before_or_equal:' . $this->referee->employment->started_at->toDateTimeString();
            }
        }

        return $rules;
    }
}
