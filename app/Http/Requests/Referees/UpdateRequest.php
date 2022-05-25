<?php

declare(strict_types=1);

namespace App\Http\Requests\Referees;

use App\Models\Referee;
use App\Rules\EmploymentStartDateCanBeChanged;
use Illuminate\Foundation\Http\FormRequest;
use Tests\RequestFactories\RefereeRequestFactory;
use Worksome\RequestFactories\Concerns\HasFactory;

class UpdateRequest extends FormRequest
{
    use HasFactory;

    public static $factory = RefereeRequestFactory::class;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('update', Referee::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $referee = $this->route()->parameter('referee');

        return [
            'first_name' => ['required', 'string', 'min:3'],
            'last_name' => ['required', 'string', 'min:3'],
            'started_at' => ['nullable', 'string', 'date', new EmploymentStartDateCanBeChanged($referee)],
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
