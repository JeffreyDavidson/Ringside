<?php

declare(strict_types=1);

namespace App\Http\Requests\Wrestlers;

use App\Models\Wrestler;
use App\Rules\LetterSpace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Tests\RequestFactories\WrestlerRequestFactory;
use Worksome\RequestFactories\Concerns\HasFactory;

class StoreRequest extends FormRequest
{
    use HasFactory;

    /** @var class-string */
    public static $factory = WrestlerRequestFactory::class;

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

        return $this->user()->can('create', Wrestler::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', new LetterSpace, 'min:3', Rule::unique('wrestlers')],
            'feet' => ['required', 'integer', 'max:8'],
            'inches' => ['required', 'integer', 'max:11'],
            'weight' => ['required', 'integer', 'digits:3'],
            'hometown' => ['required', 'string', new LetterSpace],
            'signature_move' => ['nullable', 'string', 'regex:/^[a-zA-Z\s\']+$/'],
            'start_date' => ['nullable', 'string', 'date'],
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
            'signature_move.regex' => 'The signature move only allows for letters, spaces, and apostrophes',
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
            'start_date' => 'start date',
            'signature_move' => 'signature move',
        ];
    }
}
