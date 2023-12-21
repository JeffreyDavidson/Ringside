<?php

declare(strict_types=1);

namespace App\Http\Requests\Wrestlers;

use App\Models\Wrestler;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Tests\RequestFactories\WrestlerRequestFactory;

class StoreRequest extends FormRequest
{
    /** @var class-string */
    public static $factory = WrestlerRequestFactory::class;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (is_null($this->user())) {
            return false;
        }

        return $this->user()->can('create', Wrestler::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string|Unique>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', Rule::unique('wrestlers')],
            'feet' => ['required', 'integer', 'max:8'],
            'inches' => ['required', 'integer', 'max:11'],
            'weight' => ['required', 'integer', 'digits:3'],
            'hometown' => ['required', 'string'],
            'signature_move' => ['nullable', 'string', 'regex:/^[a-zA-Z\s\']+$/'],
            'start_date' => ['nullable', 'string', 'date'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'signature_move.regex' => 'The signature move only allows for letters, spaces, and apostrophes',
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
            'start_date' => 'start date',
            'signature_move' => 'signature move',
        ];
    }
}
