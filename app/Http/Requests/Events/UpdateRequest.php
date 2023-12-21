<?php

declare(strict_types=1);

namespace App\Http\Requests\Events;

use App\Rules\EventDateCanBeChanged;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
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
     *
     * @return array<string, array<int, string|Exists|Unique|ValidationRule>>
     */
    public function rules(): array
    {
        /** @var \App\Models\Event $event */
        $event = $this->route()?->parameter('event');

        return [
            'name' => ['required', 'string', 'min:3', Rule::unique('events')->ignore($event)],
            'date' => ['nullable', 'string', 'date', new EventDateCanBeChanged($event)],
            'venue_id' => ['nullable', 'required_with:date', 'integer', Rule::exists('venues', 'id')],
            'preview' => ['nullable', 'string', 'min:3'],
        ];
    }
}
