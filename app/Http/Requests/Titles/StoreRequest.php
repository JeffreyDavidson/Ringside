<?php

declare(strict_types=1);

namespace App\Http\Requests\Titles;

use App\Models\Title;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Tests\RequestFactories\TitleRequestFactory;

class StoreRequest extends FormRequest
{
    /** @var class-string */
    public static string $factory = TitleRequestFactory::class;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (is_null($this->user())) {
            return false;
        }

        return $this->user()->can('create', Title::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string|Unique>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'ends_with:Title,Titles',
                Rule::unique('titles', 'name'),
            ],
            'activation_date' => ['nullable', 'string', 'date'],
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
            'name.regex' => 'The name only allows for letters, spaces, and apostrophes',
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
            'activation_date' => 'activation date',
        ];
    }
}
