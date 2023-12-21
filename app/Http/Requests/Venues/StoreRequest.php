<?php

declare(strict_types=1);

namespace App\Http\Requests\Venues;

use App\Models\Venue;
use Illuminate\Foundation\Http\FormRequest;
use Tests\RequestFactories\VenueRequestFactory;

class StoreRequest extends FormRequest
{
    /** @var class-string */
    public static $factory = VenueRequestFactory::class;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (is_null($this->user())) {
            return false;
        }

        return $this->user()->can('create', Venue::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'unique:App\Models\Venue,name'],
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
