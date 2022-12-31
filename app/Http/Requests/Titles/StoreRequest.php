<?php

declare(strict_types=1);

namespace App\Http\Requests\Titles;

use App\Models\Title;
use App\Rules\LetterSpace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Tests\RequestFactories\TitleRequestFactory;
use Worksome\RequestFactories\Concerns\HasFactory;

class StoreRequest extends FormRequest
{
    use HasFactory;

    /** @var class-string */
    public static $factory = TitleRequestFactory::class;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (is_null($this->user())) {
            return false;
        }

        return $this->user()->can('create', Title::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                new LetterSpace,
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
     * @return array
     */
    public function messages()
    {
        return [
            'name.regex' => 'The name only allows for letters, spaces, and apostrophes',
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
            'activation_date' => 'activation date',
        ];
    }
}
