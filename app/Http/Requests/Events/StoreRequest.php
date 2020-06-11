<?php

namespace App\Http\Requests\Events;

use App\Models\Event;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('create', Event::class);
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
                Rule::unique('events', 'name')
            ],
            'date' => [
                'nullable',
                'string',
                'date_format:Y-m-d H:i:s'
            ],
            'venue_id' => [
                'nullable',
                'integer',
                Rule::exists('venues', 'id')
            ],
            'preview' => [
                'nullable'
            ],
        ];
    }
}
