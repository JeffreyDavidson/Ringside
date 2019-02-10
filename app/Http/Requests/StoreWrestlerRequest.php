<?php

namespace App\Http\Requests;

use App\Wrestler;
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
