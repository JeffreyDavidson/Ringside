<?php

declare(strict_types=1);

namespace App\Http\Requests\Managers;

use App\Models\Manager;
use App\Rules\EmploymentStartDateCanBeChanged;
use Illuminate\Foundation\Http\FormRequest;
use Tests\RequestFactories\ManagerRequestFactory;

class UpdateRequest extends FormRequest
{
    /** @var class-string */
    public static string $factory = ManagerRequestFactory::class;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (is_null($this->user()) || is_null($this->route())) {
            return false;
        }

        if (! $this->route()->hasParameter('manager') || is_null($this->route()->parameter('manager'))) {
            return false;
        }

        return $this->user()->can('update', Manager::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, \App\Rules\EmploymentStartDateCanBeChanged|string>>
     */
    public function rules(): array
    {
        /** @var \App\Models\Manager $manager */
        $manager = $this->route()?->parameter('manager');

        return [
            'first_name' => ['required', 'string', 'min:3'],
            'last_name' => ['required', 'string', 'min:3'],
            'start_date' => ['nullable', 'string', 'date', new EmploymentStartDateCanBeChanged($manager)],
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
            'first_name' => 'first name',
            'last_name' => 'last name',
            'start_date' => 'start date',
        ];
    }
}
