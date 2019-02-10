<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWrestlerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $wrestler = $this->route('wrestler');

        return $this->user()->can('update', $wrestler);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required',
            'feet' => 'required|integer',
            'inches' => 'required|integer|max:12',
            'weight' => 'required|integer',
            'hometown' => 'required',
            'signature_move' => 'nullable',
            'hired_at' => 'required|date_format:Y-m-d H:i:s'
        ];
    }
}
