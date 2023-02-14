<?php

declare(strict_types=1);

namespace App\Http\Requests\Referees;

use App\Models\Referee;
use App\Rules\LetterSpace;
use Illuminate\Foundation\Http\FormRequest;
use Tests\RequestFactories\RefereeRequestFactory;

class StoreRequest extends FormRequest
{
    /** @var class-string */
    public static $factory = RefereeRequestFactory::class;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (is_null($this->user())) {
            return false;
        }

        return $this->user()->can('create', Referee::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', new LetterSpace, 'min:3'],
            'last_name' => ['required', 'string', new LetterSpace, 'min:3'],
            'start_date' => ['nullable', 'string', 'date'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'start_date' => 'start date',
        ];
    }
}
