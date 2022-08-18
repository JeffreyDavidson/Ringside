<?php

declare(strict_types=1);

namespace App\Http\Requests\Venues;

use App\Models\Venue;
use Illuminate\Foundation\Http\FormRequest;
use Tests\RequestFactories\VenueRequestFactory;
use Worksome\RequestFactories\Concerns\HasFactory;

class UpdateRequest extends FormRequest
{
    use HasFactory;

    /** @var class-string */
    public static $factory = VenueRequestFactory::class;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
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
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string'],
            'street_address' => ['required', 'string'],
            'city' => ['required', 'string'],
            'state' => ['required', 'string'],
            'zip' => ['required', 'integer', 'digits:5'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => 'venue name',
            'street_address' => 'street address',
            'zip' => 'zip code',
        ];
    }
}
