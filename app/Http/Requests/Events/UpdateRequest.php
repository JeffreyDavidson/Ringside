<?php

namespace App\Http\Requests\Events;

use App\Rules\EventDateCanBeChanged;
use Illuminate\Foundation\Http\FormRequest;
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
        /** @var \App\Models\Event */
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
            'name' => ['required', 'string', 'min:3', Rule::unique('events')->ignore($this->route('event')->id)],
            'date' => [
                'nullable',
                'string',
                'date',
                new EventDateCanBeChanged($this->route('event')),
            ],
            'venue_id' => ['nullable', 'integer', Rule::exists('venues', 'id')],
            'preview' => ['nullable', 'string'],
        ];
    }
}
