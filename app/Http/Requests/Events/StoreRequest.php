<?php

declare(strict_types=1);

namespace App\Http\Requests\Events;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Tests\RequestFactories\EventRequestFactory;

class StoreRequest extends FormRequest
{
    /** @var class-string */
    public static $factory = EventRequestFactory::class;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (is_null($this->user())) {
            return false;
        }

        return $this->user()->can('create', Event::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string|\Illuminate\Validation\Rules\Unique|\Illuminate\Validation\Rules\Exists>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', Rule::unique('events', 'name')],
            'date' => ['nullable', 'string', 'date'],
            'venue_id' => ['nullable', 'required_with:date', 'integer', Rule::exists('venues', 'id')],
            'preview' => ['nullable', 'string', 'min:3'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'venue_id' => 'venue',
        ];
    }
}
