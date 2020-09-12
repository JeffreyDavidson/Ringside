<?php

namespace App\Http\Requests\Referees;

use Illuminate\Foundation\Http\FormRequest;

class ReinstateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        /** @var \App\Models\Referee */
        $referee = $this->route('referee');

        return $this->user()->can('reinstate', $referee);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [];
    }
}
