<?php

declare(strict_types=1);

namespace App\Http\Requests\Managers;

use App\Models\Manager;
use App\Rules\LetterSpace;
use Illuminate\Foundation\Http\FormRequest;
use Tests\RequestFactories\ManagerRequestFactory;
use Worksome\RequestFactories\Concerns\HasFactory;

class StoreRequest extends FormRequest
{
    use HasFactory;

    /** @var class-string */
    public static $factory = ManagerRequestFactory::class;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (is_null($this->user())) {
            return false;
        }

        return $this->user()->can('create', Manager::class);
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
