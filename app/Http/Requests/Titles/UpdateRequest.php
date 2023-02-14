<?php

declare(strict_types=1);

namespace App\Http\Requests\Titles;

use App\Models\Title;
use App\Rules\ActivationStartDateCanBeChanged;
use App\Rules\LetterSpace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Tests\RequestFactories\TitleRequestFactory;
use Worksome\RequestFactories\Concerns\HasFactory;

class UpdateRequest extends FormRequest
{
    use HasFactory;

    /** @var class-string */
    public static $factory = TitleRequestFactory::class;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (is_null($this->user()) || is_null($this->route())) {
            return false;
        }

        if (! $this->route()->hasParameter('title') || is_null($this->route()->parameter('title'))) {
            return false;
        }

        return $this->user()->can('update', Title::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        /** @var \App\Models\Title $title */
        $title = $this->route()->parameter('title');

        return [
            'name' => [
                'required',
                'string',
                new LetterSpace,
                'min:3',
                'ends_with:Title,Titles',
                Rule::unique('titles')->ignore($title->id),
            ],
            'activation_date' => ['nullable', 'string', 'date', new ActivationStartDateCanBeChanged($title)],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.regex' => 'The name only allows for letters, spaces, and apostrophes',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'activation_date' => 'activation date',
        ];
    }
}
