<?php

declare(strict_types=1);

namespace App\Http\Requests\Venues;

use App\Models\Venue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Tests\RequestFactories\VenueRequestFactory;

class UpdateRequest extends FormRequest
{
    /** @var class-string */
    public static string $factory = VenueRequestFactory::class;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (is_null($this->user()) || is_null($this->route())) {
            return false;
        }

        if (! $this->route()->hasParameter('venue') || is_null($this->route()->parameter('venue'))) {
            return false;
        }

        return $this->user()->can('update', Venue::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, Unique|string>>
     */
    public function rules(): array
    {
        /** @var Venue $venue */
        $venue = $this->route()?->parameter('venue');

        return [
            'name' => ['required', 'string', 'min:3', Rule::unique('wrestlers')->ignore($venue->id)],
            'street_address' => ['required', 'string', 'min:3'],
            'city' => ['required', 'string', 'min:3'],
            'state' => ['required', 'string'],
            'zip' => ['required', 'integer', 'digits:5'],
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
            'name' => 'venue name',
            'street_address' => 'street address',
            'zip' => 'zip code',
        ];
    }
}
