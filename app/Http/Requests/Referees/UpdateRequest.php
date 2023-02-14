<?php

declare(strict_types=1);

namespace App\Http\Requests\Referees;

use App\Models\Referee;
use App\Rules\EmploymentStartDateCanBeChanged;
use App\Rules\LetterSpace;
use Illuminate\Foundation\Http\FormRequest;
use Tests\RequestFactories\RefereeRequestFactory;
use Worksome\RequestFactories\Concerns\HasFactory;

class UpdateRequest extends FormRequest
{
    use HasFactory;

    /** @var class-string */
    public static $factory = RefereeRequestFactory::class;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (is_null($this->user()) || is_null($this->route())) {
            return false;
        }

        if (! $this->route()->hasParameter('referee') || is_null($this->route()->parameter('referee'))) {
            return false;
        }

        return $this->user()->can('update', Referee::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        if (is_null($this->route()->parameter('referee'))) {
            return [];
        }

        /** @var \App\Models\Referee $referee */
        $referee = $this->route()->parameter('referee');

        return [
            'first_name' => ['required', 'string', new LetterSpace, 'min:3'],
            'last_name' => ['required', 'string', new LetterSpace, 'min:3'],
            'start_date' => ['nullable', 'string', 'date', new EmploymentStartDateCanBeChanged($referee)],
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
