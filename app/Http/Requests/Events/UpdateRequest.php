<?php

namespace App\Http\Requests\Events;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (! Auth::check()) {
            return false;
        }

        $event = $this->route('event');

        return $this->user()->can('update', $event);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['filled', 'string', Rule::unique('events')->ignore($this->route('event')->id)],
            'date' => ['sometimes', 'string', 'date_format:Y-m-d H:i:s'],
            'venue_id' => ['nullable', 'integer', 'exists:venues,id'],
            'preview' => ['nullable'],
        ];
    }
}
