<?php

declare(strict_types=1);

namespace App\Http\Requests\Events;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Tests\RequestFactories\EventRequestFactory;
use Worksome\RequestFactories\Concerns\HasFactory;

class StoreRequest extends FormRequest
{
    use HasFactory;

    public static $factory = EventRequestFactory::class;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('create', Event::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'min:3', Rule::unique('events', 'name')],
            'date' => ['nullable', 'string', 'date'],
            'venue_id' => ['nullable', 'integer', Rule::exists('venues', 'id')],
            'preview' => ['nullable', 'string', 'min:3'],
        ];
    }
}
