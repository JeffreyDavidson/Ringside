<?php

declare(strict_types=1);

namespace App\Http\Requests\Venues;

use App\Models\Venue;
use App\Rules\LetterSpace;
use Illuminate\Foundation\Http\FormRequest;
use Tests\RequestFactories\VenueRequestFactory;

class UpdateRequest extends FormRequest
{
    /** @var class-string */
    public static $factory = VenueRequestFactory::class;

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
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', new LetterSpace, 'min:3', 'unique:App\Models\Venue,name'],
            'street_address' => ['required', 'string', 'min:3'],
            'city' => ['required', 'string', 'min:3'],
            'state' => ['required', 'string'],
            'zip' => ['required', 'integer', 'digits:5'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
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
