<?php

declare(strict_types=1);

namespace App\Http\Requests\Wrestlers;

use App\Models\Wrestler;
use App\Rules\EmploymentStartDateCanBeChanged;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Tests\RequestFactories\WrestlerRequestFactory;
use Worksome\RequestFactories\Concerns\HasFactory;

class UpdateRequest extends FormRequest
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
        if (is_null($this->user()) || is_null($this->route())) {
            return false;
        }

        if (! $this->route()->hasParameter('wrestler') || is_null($this->route()->parameter('wrestler'))) {
            return false;
        }

        return $this->user()->can('update', Wrestler::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        /** @var \App\Models\Wrestler */
        $wrestler = $this->route()->parameter('wrestler');

        return [
            'name' => ['required', 'string', 'min:3', Rule::unique('wrestlers')->ignore($wrestler->id)],
            'feet' => ['required', 'integer'],
            'inches' => ['required', 'integer', 'max:11'],
            'weight' => ['required', 'integer'],
            'hometown' => ['required', 'string'],
            'signature_move' => ['nullable', 'string'],
            'start_date' => ['nullable', 'string', 'date', new EmploymentStartDateCanBeChanged($wrestler)],
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
