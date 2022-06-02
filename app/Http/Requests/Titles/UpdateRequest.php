<?php

declare(strict_types=1);

namespace App\Http\Requests\Titles;

use App\Models\Title;
use App\Rules\ActivationStartDateCanBeChanged;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Tests\RequestFactories\TitleRequestFactory;
use Worksome\RequestFactories\Concerns\HasFactory;

class UpdateRequest extends FormRequest
{
    use HasFactory;

    public static $factory = TitleRequestFactory::class;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('update', Title::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $title = $this->route()->parameter('title');

        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'ends_with:Title,Titles',
                Rule::unique('titles')->ignore($title->id),
            ],
            'activated_at' => ['nullable', 'string', 'date', new ActivationStartDateCanBeChanged($title)],
        ];
    }
}
