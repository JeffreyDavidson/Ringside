<?php

declare(strict_types=1);

namespace App\Http\Requests\Events;

use App\Rules\EventDateCanBeChanged;
use App\Rules\LetterSpace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Tests\RequestFactories\EventRequestFactory;

class UpdateRequest extends FormRequest
{
    /** @var class-string */
    public static $factory = EventRequestFactory::class;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (is_null($this->user()) || is_null($this->route())) {
            return false;
        }

        if (! $this->route()->hasParameter('event') || is_null($this->route()->parameter('event'))) {
            return false;
        }

        return $this->user()->can('update', $this->route()->parameter('event'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        /** @var \App\Models\Event $event */
        $event = $this->route()->parameter('event');

        return [
            'name' => ['required', 'string', new LetterSpace, 'min:3', Rule::unique('events')->ignore($event)],
            'date' => ['nullable', 'string', 'date', new EventDateCanBeChanged($event)],
            'venue_id' => ['nullable', 'required_with:date', 'integer', Rule::exists('venues', 'id')],
            'preview' => ['nullable', 'string', 'min:3'],
        ];
    }
}
