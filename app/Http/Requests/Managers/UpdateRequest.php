<?php

declare(strict_types=1);

namespace App\Http\Requests\Managers;

use App\Models\Manager;
use App\Rules\EmploymentStartDateCanBeChanged;
use Illuminate\Foundation\Http\FormRequest;
use Tests\RequestFactories\ManagerRequestFactory;
use Worksome\RequestFactories\Concerns\HasFactory;

class UpdateRequest extends FormRequest
{
    use HasFactory;

    public static $factory = ManagerRequestFactory::class;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('update', Manager::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $manager = $this->route()->parameter('manager');

        return [
            'first_name' => ['required', 'string', 'min:3'],
            'last_name' => ['required', 'string', 'min:3'],
            'started_at' => ['nullable', 'string', 'date', new EmploymentStartDateCanBeChanged($manager)],
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
            'first_name' => 'first name',
            'last_name' => 'last name',
            'started_at' => 'started at',
        ];
    }
}
