<?php

declare(strict_types=1);

namespace App\Http\Requests\Events;

use App\Rules\EventDateCanBeChanged;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Tests\RequestFactories\EventRequestFactory;
use Worksome\RequestFactories\Concerns\HasFactory;

class UpdateRequest extends FormRequest
{
    use HasFactory;

    /** @var class-string */
    public static $factory = EventRequestFactory::class;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (is_null($this->user())) {
            return false;
        }

        $event = $this->route()->parameter('event');

        return $this->user()->can('update', $event);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        /** @var \App\Models\Event */
        $event = $this->route()->parameter('event');

        return [
            'name' => ['required', 'string', 'min:3', Rule::unique('events')->ignore($event)],
            'date' => ['nullable', 'string', 'date', new EventDateCanBeChanged($event)],
            'venue_id' => ['nullable', 'integer', Rule::exists('venues', 'id')],
            'preview' => ['nullable', 'string'],
        ];
    }
}
